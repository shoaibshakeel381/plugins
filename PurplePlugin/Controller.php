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
        $view->setColumnTranslation('total_time', 'Time Spent (ms)');
        
        $view->setSortedColumn('username', 'asc');
        $view->setLimit(24);
        $view->disableSearchBox();
        $view->disableRowEvolution();
        $view->disableFooterIcons();
        $view->disableExcludeLowPopulation();
        
        return $this->renderView($view);
    }
            
    function displayCustomTemplate()
    {
        $view = Piwik_View::factory('PurpleGraph'); 
        $view->users = $this->getUsers();
        $view->pages = $this->getPages();
        //$view->PurpleGraph1 = $this->displayGraph();
        
        echo $view->render();
    }
    
    function testGraph()
    {
        $userid = Piwik_Common::getRequestVar('userid', null, 'int');
        $text = "You Fucking Selected <b>$userid</b> user";
        echo $text;
    }
    
    function displayGraph()
    {
        $view = Piwik_ViewDataTable::factory('graphVerticalBar');
        $view->init($this->pluginName, __FUNCTION__, 'PurplePlugin.getGraphData');
        $view->setColumnTranslation('label', 'User Name');
        $view->setColumnTranslation('total_time', 'Total Time (min)');
//        $view->setColumnTranslation('value', "Temperature");
        $view->setAxisYUnit(' min');
        $view->setSortedColumn('total_time', 'dsc');
//        $view->setGraphLimit(5);
//        $view->disableFooter();
        return $this->renderView($view);
//        $view->render();
    }
    
    function getUsers()
    {
        $query = "SELECT app_users.iduser as userid, app_users.username as username
                    FROM app_users";
        $results = Piwik_FetchAll($query);

        return $results;
    }
    
    function getPages()
    {
        $query = "SELECT app_products.idpage as pageid, app_products.name as productname
                    FROM app_products";
        $results = Piwik_FetchAll($query);

        return $results;
    }
 
    function setSession()
    {
        $_SESSION['PurpleId'] = '03214';
        $_SESSION['PurpleName'] = 'Cashif';
    }

    static private function boolToString($bool)
    {
        return $bool ? "true" : "false";
    }
}
