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

    public function getSessionTable($idSite, $date, $period) {
        $result = $this->getdateRange($idSite, $date, $period);
        $where = "AND visit_first_action_time >= '".$result[0]."' AND visit_first_action_time <= '".$result[1]. "'";
        
        $sample_array = array();
//        $sample_array[] = array('username'=>$idSite, 'idpage'=>$date, 'total_time'=>$period);
//        $sample_array[] = array('username'=>$idSite, 'idpage'=>$result[0], 'total_time'=>$result[1]);
        
        //This is my code
        $query = "SELECT iduser, idpage ".
                "FROM ".Piwik_Common::prefixTable('log_visit')." WHERE idsite = ". $idSite .
                " AND (idpage!=0 AND idpage IS NOT NULL) ". $where ." GROUP BY iduser, idpage ".
                "ORDER BY iduser asc";
        printd($query);
        $result = Piwik_FetchAll($query); 
        //return print_r($result, true);
        
        //now i am building an array of these results
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
        $dataTable->addRowsFromSimpleArray($sample_array);
        return $dataTable;
    }
    
    /**
     *  This function returns corrent start and end dates for result to view
     * @param type $idSite  siteid
     * @param type $date    selected date
     * @param type $period  selected period
     */
    private function getdateRange($idSite, $date, $period){
        ////////////////////////////////////////////////////
        //this are ensure correct reults are fetched for selected period and date
        $oSite = new Piwik_Site($idSite);
        $result[0] = '';
        $result[1] = '';
        
        if($period=='range'){       //then show result from dateStart to dateEnd
            $newperiod = new Piwik_Period_Range($period, $date, $oSite->getTimezone());
            $result[0] = $newperiod->getDateStart()->toString('Y-m-d'). " 00:00:00"; // eg. "2009-04-01"
            $result[1] = $newperiod->getDateEnd()->toString('Y-m-d'). ' 23:59:59'; // eg. "2009-04-30"
        } else if ($period=='day'){
            $result[0] = $date;
            $result[1] = $date;
        } else {
            $duedt = explode("-", $date);
            $duedt  = mktime(0, 0, 0, $duedt[1], $duedt[2], $duedt[0]);

            $week  = (int)date('W', $duedt);
            $year = (int)date('Y', $duedt);
            $month = (int)date('m', $duedt);
            
            if($period=='week'){
                $date_string = $year . 'W' . sprintf('%02d', $week);
                $result[0] = date('Y-m-d', strtotime($date_string));
                $result[1] = date('Y-m-d', strtotime($date_string . '7'));
            } else if($period=='month'){
                $result[0] = date('Y-m-d', mktime(0, 0, 0, $month, 1, $year));
                $result[1] = date('Y-m-t', mktime(0, 0, 0, $month, 1, $year));
            } else if($period=='year'){
                $result[0] = date('Y-m-d', mktime(0, 0, 0, 1, 1, $year));
                $result[1] = date('Y-m-t', mktime(0, 0, 0, 12, 1, $year));
            }
        }
        
        $result[0] = $result[0]. " 00:00:00"; // eg. "2009-04-01"
        $result[1] = $result[1]. ' 23:59:59'; // eg. "2009-04-30"
        
        return $result;
    }
    
    /**
     * return username from other sites db
     * @param type $userId
     * @return username or null
     */
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
