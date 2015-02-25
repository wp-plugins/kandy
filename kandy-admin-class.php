<?php

class KandyAdmin {
    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        load_plugin_textdomain( 'kandy', false, KANDY_PLUGIN_DIR. "/languages" );
    }

    public function admin_menu() {
        add_menu_page(
            "Kandy Configuration",
            "Kandy",
            "administrator",
            "kandy",
            null,
            KANDY_PLUGIN_URL . "/img/kandy-wp.png"
        );
        add_submenu_page(
            "kandy",
            "Kandy Settings",
            "Settings",
            "administrator",
            "kandy",
            array($this, "kandy_admin_pages")
        );
        add_submenu_page(
            "kandy",
            "Kandy User Assignment",
            "User Assignment",
            "administrator",
            "kandy-user-assignment",
            array($this, "kandy_admin_pages")
        );

        add_submenu_page(
            "kandy",
            "Kandy Customization",
            "Customization",
            "administrator",
            "kandy-customization",
            array($this, "kandy_admin_pages")
        );

        add_submenu_page(
            "kandy",
            "Kandy Help",
            "Help",
            "administrator",
            "kandy-help",
            array($this, "kandy_admin_pages")
        );
    }

    function kandy_admin_pages() {
        switch ($_GET["page"]) {
            case "kandy" :
                require_once (dirname(__FILE__) . "/admin/SettingsPage.php");
                $kandySettingPage = new KandySettingsPage();
                $kandySettingPage->render();
                break;
            case "kandy-user-assignment" :
                if(isset($_GET['action'])){
                    $action = $_GET['action'];
                    if($action == "edit"){
                        require_once (dirname(__FILE__) . "/admin/AssignmentEditPage.php");
                        $kandyAssignmentPage = new KandyAssignmentEditPage();
                        $kandyAssignmentPage->render();
                    } elseif($action == "sync"){
                        require_once (dirname(__FILE__) . "/admin/AssignmentSyncPage.php");
                        $kandyAssignmentPage = new KandyAssignmentSyncPage();
                        $kandyAssignmentPage->render();
                    } else{
                        require_once (dirname(__FILE__) . "/admin/AssignmentPage.php");
                        $kandyAssignmentPage = new KandyAssignmentPage();
                        $kandyAssignmentPage->render();
                    }
                } else {
                    require_once (dirname(__FILE__) . "/admin/AssignmentPage.php");
                    $kandyAssignmentPage = new KandyAssignmentPage();
                    $kandyAssignmentPage->render();
                }

                break;
            case "kandy-customization" :
                if(isset($_GET['action'])){
                    $action = $_GET['action'];
                    if($action == "edit"){
                        require_once (dirname(__FILE__) . "/admin/CustomizationEditPage.php");
                        $kandyCustomizationEditPage = new KandyCustomizationEditPage();
                        $kandyCustomizationEditPage->render();
                    } else {
                        require_once (dirname(__FILE__) . "/admin/CustomizationPage.php");
                        $kandyCustomizationPage = new KandyCustomizationPage();
                        $kandyCustomizationPage->render();
                    }

                } else {
                    require_once (dirname(__FILE__) . "/admin/CustomizationPage.php");
                    $kandyCustomizationPage = new KandyCustomizationPage();
                    $kandyCustomizationPage->render();
                }

                break;
            case "kandy-help" :
                require_once (dirname(__FILE__) . "/admin/HelpPage.php");
                $kandySettingPage = new KandyHelpPage();
                $kandySettingPage->render();
                break;
        }
    }
}
