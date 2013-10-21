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
//        $obj = new Piwik_PurplePlugin_API();
//        echo $obj->getSessionTable(1);
//        exit;
        $view->init($this->pluginName, __FUNCTION__, 'PurplePlugin.getSessionTable');
        $view->setColumnsToDisplay(array('username','idpage','total_time'));
        
        $view->setColumnTranslation('username', 'User Name');
        $view->setColumnTranslation('idpage', 'Page Number');
        $view->setColumnTranslation('total_time', 'Time Spent (sec)');
        
        $view->setSortedColumn('username', 'asc');
        $view->setLimit(24);
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

        $view->setGraphLimit(5);
        $view->disableSearchBox();
        $view->disableShowAllColumns();
        $view->disableShowTable();
        return $this->renderView($view);
    }
    
    private function getUsers()
    {
        $res = array();
        $connect=NULL;
        
        if(!($connect=mysql_connect('localhost', 'db_admin', 'super1'))){
            return $res;
        }
        
        if(!mysql_selectdb('plugin_users_db', $connect)){
            return $res;
        }
        
        $query="SELECT id, fname, lname FROM users";
        $result = mysql_query($query, $connect);
        if(mysql_num_rows($result)>0){
            
            while($val = mysql_fetch_assoc($result)){
                $res[] = array('id'=>$val['id'], 'username'=>$val['fname'].' '.$val['lname']);
            }
        }
        
        return $res;
    }
    
    private function getPages()
    {
        $idSite = Piwik_Common::getRequestVar('idSite', 0, 'int');
        
        $query = "SELECT DISTINCT(idpage) as pageid FROM ".Piwik_Common::prefixTable('log_visit')." WHERE idsite = ". $idSite .
                " AND (idpage!=0 AND idpage IS NOT NULL)";
        //printd($query);
        $results = Piwik_FetchAll($query);

        return $results;
    }
}
