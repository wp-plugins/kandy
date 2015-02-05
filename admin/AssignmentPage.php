<?php
require_once (dirname(__FILE__) . "/KandyPage.php");
require_once (dirname(__FILE__) . "/AssignmentTableList.php");
require_once (KANDY_PLUGIN_DIR . "/api/kandy-api-class.php");
class KandyAssignmentPage extends KandyPage
{
    public function render()
    {

        $this->render_page_start("Kandy");
        ?>

        <h3>
            <?php _e("Kandy User Assignment", "kandy"); ?>
            <a href="<?php echo Kandyapi::page_url(array('action' =>'sync')); ?>" class="button-primary"><?php _e("Sync", "kandy"); ?></a></h3>
        <?php
        $this->render_all_messages();
        $assignmentTableList = new AssignTableList();
        $assignmentTableList->prepare_items();
        $assignmentTableList->display();
        $this->render_page_end();



    }
}

?>
