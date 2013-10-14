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
 * PurplePlugin API is also an example API useful if you are developing a Piwik plugin.
 *
 * The functions listed in this API are returning the data used in the Controller to draw graphs and
 * display tables. See also the ExampleAPI plugin for an introduction to Piwik APIs.
 *
 * @package Piwik_PurplePlugin
 */
class Piwik_PurplePlugin_API {

    static private $instance = null;

    static public function getInstance() {
        if (self::$instance == null) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function getSessionTable($idSite) {
        //This is my code
        $query = "SELECT iduser, idpage ".
                "FROM ".Piwik_Common::prefixTable('log_visit')." WHERE idsite = ". $idSite .
                " AND (idpage!=0 AND idpage IS NOT NULL) GROUP BY iduser, idpage ".
                "ORDER BY iduser asc";
        //printd($query);
        $result = Piwik_FetchAll($query); 
        
        //return print_r($result, true);
        
        //now i am building an array of these results
        $sample_array = array();
        $prevuserId = 0;
        $prevuserName = '';
        for($i=0; $i < count($result); $i++){
            //we want to optimize number of db requests. So if a repeated user appears
            //use previously found username
            //for this to work we have to order the result by iduser
            if($result[$i]['iduser'] != $prevuserId){
                $temp = $this->getUserName($result[$i]['iduser']);
                $prevuserName = $temp!=null ? $temp : $result[$i]['iduser'];
            }
            $q = "SELECT SUM(pagetime) as time_spent FROM ".Piwik_Common::prefixTable('log_visit')." WHERE idsite = ". $idSite .
                " AND idpage={$result[$i]['idpage']} AND iduser={$result[$i]['iduser']}";
            printd($q);
            $timeSpent = Piwik_FetchAll($q);
            $sample_array[] = array('username'=>$prevuserName, 'idpage'=>$result[$i]['idpage'], 'total_time'=>$timeSpent[0]['time_spent']);
        }
        
//        return print_r($sample_array, true);

        $dataTable = new Piwik_DataTable();
//        $dataTable->addRowsFromArrayWithIndexLabel($result);
        $dataTable->addRowsFromSimpleArray($sample_array);
        return $dataTable;
    }
    
    private function getUserName($userId){
        $connect=NULL;
        
        if(!($connect=mysql_connect('localhost', 'db_admin', 'super1'))){
            return null;
        }
        
        if(!mysql_selectdb('plugin_users_db', $connect)){
            return null;
        }
        
        $query="SELECT fname, lname FROM users WHERE id={$userId} LIMIT 1";
        $result = mysql_query($query, $connect);
        if(mysql_num_rows($result)>0){
            $res = mysql_fetch_assoc($result);
            return $res['fname'] .' '. $res['lname'];
        }
        
        return null;
    }

    public function getGraphData($idSite) {
        $userid = Piwik_Common::getRequestVar('userid', 0, 'int');
        $pageid = Piwik_Common::getRequestVar('pageid', 0, 'int');

        printDebug(" -- userid : " . $userid . " -- pageid:". $pageid);

        $sample_array = array();

        if ($userid != 0 && $pageid != 0) 
        {
            $query = "SELECT piwik_log_visit.idpage as label, SUM(visit_total_time)/60 as total_time 
                    FROM piwik_log_visit,app_users 
                    WHERE piwik_log_visit.iduser = app_users.iduser 
                        AND piwik_log_visit.iduser = $userid 
                        AND piwik_log_visit.idpage = $pageid
                        AND piwik_log_visit.idsite = $idSite 
                    GROUP BY piwik_log_visit.iduser, piwik_log_visit.idpage";
        } 
        else if ($userid != 0 && $pageid == 0) 
        {
            $query = "SELECT piwik_log_visit.idpage as label, SUM(visit_total_time)/60 as total_time 
                    FROM piwik_log_visit,app_users 
                    WHERE piwik_log_visit.iduser = app_users.iduser 
                        AND piwik_log_visit.iduser = $userid 
                        AND piwik_log_visit.idsite = $idSite 
                    GROUP BY piwik_log_visit.iduser, piwik_log_visit.idpage";
        }
        else if ($userid == 0 && $pageid != 0) 
        {
            $query = "SELECT app_users.username as label, SUM(visit_total_time)/60 as total_time 
                    FROM piwik_log_visit,app_users 
                    WHERE piwik_log_visit.iduser = app_users.iduser 
                        AND piwik_log_visit.idpage = $pageid
                        AND piwik_log_visit.idsite = $idSite 
                    GROUP BY piwik_log_visit.iduser, piwik_log_visit.idpage";
        }
        else 
        {
//          $query = "SELECT app_users.username, app_products.name, SUM(visit_total_time) as total_time FROM piwik_log_visit,app_users,app_products WHERE piwik_log_visit.iduser = app_users.iduser AND piwik_log_visit.idpage = app_products.idpage AND piwik_log_visit.idsite = $idSite GROUP BY piwik_log_visit.iduser, piwik_log_visit.idpage";
            $query = "SELECT app_users.username as label, SUM(visit_total_time)/60 as total_time 
                    FROM piwik_log_visit,app_users 
                    WHERE piwik_log_visit.iduser = app_users.iduser  
                        AND piwik_log_visit.idsite = $idSite 
                    GROUP BY piwik_log_visit.iduser";
        }

        $result = Piwik_FetchAll($query);

        $dataTable = new Piwik_DataTable();
        
        $dataTable->addRowsFromSimpleArray($result);

        return $dataTable;
    }

}
