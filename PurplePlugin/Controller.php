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
class Piwik_PurplePlugin_Controller extends Piwik_Controller
{
    /**
     * See the result on piwik/?module=PurplePlugin&action=exampleWidget
     * or in the dashboard > Add a new widget
     * This function will 
     */
    function displaySession()
    {
        $view = Piwik_ViewDataTable::factory('table');
        $view->init($this->pluginName, __FUNCTION__, 'PurplePlugin.getSessionTable');
        $view->setColumnsToDisplay(array('username','idpage','total_time'));
        
        $view->setColumnTranslation('username', 'User Name');
        $view->setColumnTranslation('idpage', 'Page Number');
        $view->setColumnTranslation('total_time', 'Time Spent (sec)');
        
        $view->setSortedColumn('username', 'asc');
        $view->setLimit(10);
        $view->disableSearchBox();
        $view->disableRowEvolution();
        $view->disableFooterIcons();
        $view->disableExcludeLowPopulation();
        return $this->renderView($view);
    }
    
    /**
     * Initialize custome template for graphs of time spent on each page by users
     */
    function displayCustomTemplate()
    {
        $view = Piwik_View::factory('PurpleGraph');
        $view->users = $this->getUsers();
        $view->pages = $this->getPages();
        echo $view->render(); //echo
    }
    
    /**
     * This function will render graph whenever a new choice is made
     * or widget loads for the first time
     * @return type
     */
    function displayGraph()
    {
        $view = Piwik_ViewDataTable::factory('graphVerticalBar');
        $view->init($this->pluginName, __FUNCTION__, 'PurplePlugin.getGraphData');
        $view->setColumnTranslation('label', 'Page Number');
        $view->setColumnTranslation('time_spent', 'Total Time (sec)');
        $view->setAxisYUnit(' sec');
        $view->setSortedColumn('total_time', 'dsc');

        $view->setGraphLimit(10);
        $view->disableSearchBox();
        $view->disableShowAllColumns();
        $view->disableShowTable();
        return $this->renderView($view);
    }
    
    private function getUsers()
    {
        $idSite = Piwik_Common::getRequestVar('idSite', 0, 'int');
        
        $query = "SELECT DISTINCT(iduser) as user FROM ".Piwik_Common::prefixTable('log_visit')." WHERE idsite='{$idSite}' ".
                "AND iduser!='0'";
        //printd($query);
        $results = Piwik_FetchAll($query);

        return $results;
    }
    
    private function getPages()
    {
        $idSite = Piwik_Common::getRequestVar('idSite', 0, 'int');
        
        $query = "SELECT DISTINCT(idpage) as pageid FROM ".Piwik_Common::prefixTable('log_visit')." WHERE idsite='{$idSite}' ".
                "AND idpage!=0";
        //printd($query);
        $results = Piwik_FetchAll($query);

        return $results;
    }
    
    function ping(){
        $iduser = Piwik_Common::getRequestVar('userid', 0);
        $idSite = Piwik_Common::getRequestVar('siteid', 0, 'int');
        $idpage = Piwik_Common::getRequestVar('pageid', 0, 'int');
        $time = Piwik_Common::getRequestVar('time', 0, 'int');
        
        //printd(Piwik_PurplePlugin::$working?"true":"false");
        while(Piwik_PurplePlugin::$working){}
        printd(Piwik_PurplePlugin::$working?"true":"false");
        $query = "SELECT idvisit, pagetime FROM ".Piwik_Common::prefixTable('log_visit')
                ." WHERE idsite='{$idSite}' AND iduser='{$iduser}' AND idpage='{$idpage}' ORDER BY visit_last_action_time DESC LIMIT 1";
        //echo "\n".$query;
        $result = Piwik_FetchAll($query);
        if(count($result)>0){
            $totalTime = (int) $result[0]['pagetime'] + $time;
            $query = "UPDATE ".Piwik_Common::prefixTable('log_visit')
                    ." SET pagetime='{$totalTime}' WHERE idvisit='{$result[0]['idvisit']}' LIMIT 1";
            //echo "\n".$query;
            echo Piwik_Exec($query);
            printd('done');
        }else {
            printd("row wasn't present");
        }
        exit;
    }
}
