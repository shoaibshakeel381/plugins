<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 * @category Piwik_Plugins
 * @package Piwik_PurplePlugin
 */

/**
 *
 * @package Piwik_PurplePlugin
 */
class Piwik_PurplePlugin extends Piwik_Plugin
{
    public static $working = false;    //this is used to synchronize time update with page visits
    /**
     * Return information about this plugin.
     *
     * @see Piwik_Plugin
     *
     * @return array
     */
    public function getInformation()
    {
        return array(
            'description'          => 'PurplePlugin | An Experimental Plugin',
            'homepage'             => 'http://www.purplefellows.com/',
            'author'               => 'Shoaib Shakeel',
            'author_homepage'      => 'mailto:shoaib.shakeel@purplefellows.com',
            'license'              => 'GPL v3 or later',
            'license_homepage'     => 'http://www.gnu.org/licenses/gpl.html',
            'version'              => '0.1',
            'translationAvailable' => false,
            'TrackerPlugin'   => true, // this plugin must be loaded during the stats logging
        );
    }

    public function getListHooksRegistered()
    {
        return array(
//	    'Controller.renderView' => 'addUniqueVisitorsColumnToGivenReport',
            'Tracker.newVisitorInformation' => 'logNewVisitInfo',
            'Tracker.saveVisitorInformation.end' => 'loggedNewVisitInfo',
            'Tracker.knownVisitorUpdate' => 'logKnwonVisitInfo',
            'Tracker.knownVisitorInformation' => 'correctKnwonVisitInfo',
            'WidgetsList.add' => 'addWidgets',
            'Menu.add' => 'addMenu'
        );
    }
    
    public function install()
    {
      // add column for userid and productname
      $query = "ALTER IGNORE TABLE `".Piwik_Common::prefixTable('log_visit')."` " .
                       "ADD `iduser` INT NULL, ".
                       "ADD `idpage` INT NULL, ".
                       "ADD `pagetime` INT NULL";

      // if the column already exist do not throw error. Could be installed twice...
      try 
      {
        Piwik_Exec($query);
      }
      catch(Exception $e){
          //printd('Error in MYSQL query during activation or install');
          //printd("Query was : ".$query);
      }
    }

    function activate()
    {
        $this->install();
        // Executed every time plugin is Enabled
    }

    function deactivate()
    {
        // Executed every time plugin is disabled
    }
    
    /**
     * Function to log userid and productid into database for a new visitor
     */
    function logNewVisitInfo($notification) {
        $this->working = true;
        $this->customizedUpdate = 0;          //totaly unnecessary
        printd("PurplePlugin: New Visitor: called!!");
        $visitorInfo = & $notification->getNotificationObject();
        // Access data variable that contains iduser and idpage
        try{
            $customdata = Piwik_Common::getRequestVar('data');
        }  catch(Exception $ex){
            printd("PurplePlugin: Visitor: Exception Handled (No Custom Data)");
            printd("===================================================================");
            return;
        }
        
        // Remove Quotes from string
        $customdata = preg_replace("/[^a-zA-Z0-9,]+/", "", html_entity_decode($customdata, ENT_QUOTES));
        // Split the data by delimiter 'comma'
        $customdata = explode(',', $customdata);
        printd("[Purple Plugin] Found Custom Data: \n" . var_export($customdata, true));
        //First number is iduser
        $iduser = isset($customdata[0]) ? $customdata[0] : null;
        //Second Number is idpage
        $idpage = isset($customdata[1]) ? $customdata[1] : null;
        //This is the siteId
        $idSite = isset($customdata[2]) ? $customdata[2] : $visitorInfo['idsite'];

        if($iduser == NULL || $idpage == NULL){
            printd("Visit is not on document page");
            printd("===================================================================");
            return;
        } else{
            printd("Visit is on document page");
        } //else visit is on a document page so log it.
        
        $visitorInfo['iduser'] = $iduser;
        $visitorInfo['idpage'] = $idpage;
        $visitorInfo['pagetime'] = 0;       //sets page time to zero
        //printd("This is the visitorInfo:\n". var_export($visitorInfo, true));
    }
    
    function loggedNewVisitInfo($notification){
        printd("New Visitor Information was logged");
        $this->working = false;
        printd("===================================================================");
    }
    
    
    /**
     * @var array   $currentrow         This variable holds backup data of row which should be updated
     * @var array   $rowBackup          This variable holds backup data of row which is to be updated
     * @var boolean $customizedUpdate   if 0 we will replace current updated row with original
     *                                  if 1 we arete a new row and restore updated row
     *                                  if 2 we update another row and restore current updated row
     */
    private $rowBackup = '';
    private $currentrow = '';
    private $customizedUpdate = 0;
    /**
     * Function to log userid and productid into database for a known visitor
     * 
     * @param type $notification contains visitorInfo
     */
    function logKnwonVisitInfo($notification) {
        $this->working = true;
        $this->customizedUpdate = 0;;
        printd("PurplePlugin: KNWON Visitor: called!!");
        $visitorInfo = & $notification->getNotificationObject();
//        printd("This is the visitorInfo:\n". var_export($visitorInfo, true));

        // Access data variable that contains iduser and idpage
        try{
            $customdata = Piwik_Common::getRequestVar('data');
        }  catch(Exception $ex){
            printd("PurplePlugin: Visitor: Exception Handled (No Custom Data)");
            printd("===================================================================");
            return;
        }
        
        // Remove Quotes from string
        $customdata = preg_replace("/[^a-zA-Z0-9,]+/", "", html_entity_decode($customdata, ENT_QUOTES));
        // Split the data by delimiter 'comma'
        $customdata = explode(',', $customdata);
        //printd("[Purple Plugin] Found Custom Data: \n" . var_export($customdata, true));
        //First number is iduser
        $iduser = isset($customdata[0]) ? $customdata[0] : null;
        //Second Number is idpage
        $idpage = isset($customdata[1]) ? $customdata[1] : null;
        //This is the siteId
        $idSite = isset($customdata[2]) ? $customdata[2] : 1;

        if($iduser == NULL || $idpage == NULL){
            printd("Visit is not on document page");
            printd("===================================================================");
            return;
        } else{
            printd("Visit is on document page");
        } //else visit is on a document page so log it.
        
        //these values are accessed in creating new row after update finishes
        $visitorInfo['iduser'] = $iduser;
        $visitorInfo['idpage'] = $idpage;
        $visitorInfo['pagetime'] = 0;
        
        //select the row which is going to be updated and backup its data
        $query = "SELECT * FROM `".Piwik_Common::prefixTable('log_visit').
                "` WHERE `idsite`={$idSite} AND `idvisitor`=x'".bin2hex($visitorInfo['idvisitor']).
                "' ORDER BY `visit_last_action_time` DESC LIMIT 1";
        ////printd("This is the query: ".$query);
        $this->rowBackup = Piwik_FetchAll($query);
        
        //or we could something like this:
        //First Select a row with same pagenumber and userid etc.
        //   if this row is not present just set reviewUpdate true
        //   if this row is present check wether is it the one which is going to be updated 
        //       if yes then  no problem set reviewUpdate to false
        //       if no then check whether last action was within 30mins or not
        //           if last action was within 30mins update it
        //           if last action was beyond 30mins set reviewUpdate true
        $query = "SELECT * FROM `".Piwik_Common::prefixTable('log_visit').
                "` WHERE `idsite`={$idSite} AND `idvisitor`=x'".bin2hex($visitorInfo['idvisitor']).
                "' AND `idpage`={$idpage} AND `iduser`={$iduser} ORDER BY `visit_last_action_time` DESC LIMIT 1";
        
        ////printd("This is the query: ".$query);
        $this->currentrow = Piwik_FetchAll($query);
        
        if(count($this->currentrow[0])>0){
            //row is present
            if($this->rowBackup[0]['idvisit']==$this->currentrow[0]['idvisit']){
                printd('same row is selected for update operation');
            }else {
                printd("row is present but is not the row which should be updated");
                $lastactiontime = $this->currentrow[0]['visit_last_action_time'];
                if($lastactiontime > (time()-Piwik_Config::getInstance()->Tracker['visit_standard_length'])){
                    printd("sesion expired so create new row");
                    $this->customizedUpdate = 1;
                } else{
                    printd("session is not expired so do not create new row. update this row");
                    $this->customizedUpdate = 2;
                    $visitorInfo['pagetime'] = $this->currentrow[0]['pagetime'];
                    printd("pagetime of current row is: ".$visitorInfo['pagetime']);
                }
            }
        }else{
            printd("row is not present");
            $this->customizedUpdate = 1;
        }
        
        printd("===================================================================");
    }
    
    function correctKnwonVisitInfo($notification) {
        printd("PurplePlugin: UPDATED KNOWN Visitor called!!");
        $visitorInfo = & $notification->getNotificationObject();
//        //printd("This is the visitorInfo:\n". var_export($visitorInfo, true));
        
        if($this->customizedUpdate===1){
            printd("different row was updated so we need to change information");
            
            //First fetch new data and insert it into new row.
            $query = "SELECT * FROM `".Piwik_Common::prefixTable('log_visit').
                "` WHERE `idvisit`={$visitorInfo['idvisit']} LIMIT 1";
            
            //printd('The First Select query is: '.$query);
            $result = Piwik_FetchAll($query);
            printd("Values USERID:".$result[0]['iduser']." PAGEID:".$result[0]['idpage']." VISITID:".$result[0]['idvisit']);
            //this will build up query to execute
            $set = "";
            foreach($result[0] as $key => $value){
                if($key=='idvisit')continue;
                
                //if a binary value appears convert it to hex first
                if($key=='pagetime') {
                    $set.= "`{$key}`='0'";
                }else if($key=='idvisitor' || $key=='config_id' || $key=='location_ip'){
                    $set.= "`{$key}`=X'".bin2hex($value)."', ";
                } else {
                    $set.= "`{$key}`='{$value}', ";
                }
            }
            $q = "INSERT INTO `".Piwik_Common::prefixTable('log_visit')."` SET {$set}";
            ////printd($q);
            if(Piwik_Query($q)>0){
                printd("Query executed and data inerted into table");
            } else{
                printd("Query not executed and data not inerted into table");
            }
            
            //after inserting data into new row we need to restor reviouslt updated row
            $prevRow = $visitorInfo['idvisit']; //this is the previously updated row
            
            //this will build up update query
            $set = "";
            foreach($this->rowBackup[0] as $key => $value){
                if($key=='idvisit')continue;
                if($key=='pagetime' && $value==NULL) $value=0;
                
                //if a binary value appears convert it to hex first
                if($key=='pagetime') {
                    $set.= "`{$key}`='{$value}'";
                }else if($key=='idvisitor' || $key=='config_id' || $key=='location_ip'){
                    $set.= "`{$key}`=X'".bin2hex($value)."', ";
                } else {
                    $set.= "`{$key}`='{$value}', ";
                }
            }
            $q = "UPDATE `".Piwik_Common::prefixTable('log_visit')."` SET {$set} WHERE `idvisit`={$prevRow}";
            ////printd($q);
            if(Piwik_Query($q)>0){
                printd("Query executed and data updated into table");
            } else{
                printd("Query not executed and data not updated into table");
            }
            printd("new row operation completed.");
        }else if($this->customizedUpdate===2){
            //we have to update current row instead of creating new row
            printd("different row was updated so we need to change information");
            
            //First fetch new data and insert it into new row.
            $query = "SELECT * FROM `".Piwik_Common::prefixTable('log_visit').
                "` WHERE `idvisit`={$visitorInfo['idvisit']} LIMIT 1";
            
//            //printd('The First Select query is: '.$query);
            $result = Piwik_FetchAll($query);
            
            $set = "`visit_exit_idaction_name`='{$result[0]['visit_exit_idaction_name']}',".
                   " `visit_exit_idaction_url`='{$result[0]['visit_exit_idaction_url']}',".
                   " `visit_last_action_time`='{$result[0]['visit_last_action_time']}', ".
                   "`visit_total_time`='{$result[0]['visit_total_time']}',".
                   " `idvisitor`=X'".bin2hex($result[0]['idvisitor'])."',".
                   " `visit_goal_buyer`='{$result[0]['visit_goal_buyer']}',".
                   "`iduser`='{$result[0]['iduser']}',".
                   "`idpage`='{$result[0]['idpage']}',".
                   "`pagetime`='{$result[0]['pagetime']}' ";
            
            $q = "UPDATE `".Piwik_Common::prefixTable('log_visit')."` SET {$set} WHERE `idvisit`='{$this->currentrow[0]['idvisit']}'";
            //printd($q);
            if(Piwik_Query($q)>0){
                printd("Query executed and data inerted into table");
            } else{
                printd("Query not executed and data not inerted into table");
            }
            
            //after inserting data into already present row we need to restor previousiy updated row
            //this will build up update query
            $set = "";
            foreach($this->rowBackup[0] as $key => $value){
                if($key=='idvisit')continue;
                if($key=='pagetime' && $value==NULL) $value=0;
                
                //if a binary value appears convert it to hex first
                if($key=='pagetime') {
                    $set.= "`{$key}`='{$value}'";
                }else if($key=='idvisitor' || $key=='config_id' || $key=='location_ip'){
                    $set.= "`{$key}`=X'".bin2hex($value)."', ";
                } else {
                    $set.= "`{$key}`='{$value}', ";
                }
            }
            $q = "UPDATE `".Piwik_Common::prefixTable('log_visit')."` SET {$set} WHERE `idvisit`={$visitorInfo['idvisit']}";
            ////printd($q);
            if(Piwik_Query($q)>0){
                printd("Query executed and data updated into table");
            } else{
                printd("Query not executed and data not updated into table");
            }
            printd("row update operation completed.");
        }else{
            printd("same row was updated so no need to change anything");
        }
        $this->working = false;
        printd("===================================================================");
    }
    
    function addWidgets()
    {
        // we register the widgets so they appear in the "Add a new widget" window in the dashboard
        // Note that the first two parameters can be either a normal string, or an index to a translation string
        Piwik_AddWidget('purpleWidgets', 'Purple Widget', 'PurplePlugin', 'displaySession');
        Piwik_AddWidget('purpleWidgets', 'Purple Graph', 'PurplePlugin', 'displayCustomTemplate');
    }
    
    function addMenu()
    {
        Piwik_AddMenu('General_Visitors', 'Purple Plugin', array('module' => 'PurplePlugin', 'action' => 'displayCustomTemplate'));
    }
}
