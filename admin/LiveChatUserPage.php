<?php
/**
 * Created by PhpStorm.
 * User: Khanh
 * Date: 17/6/2015
 * Time: 3:26 PM
 */
require_once (dirname(__FILE__) . "/KandyPage.php");
require_once (KANDY_PLUGIN_DIR . "/api/kandy-api-class.php");

class LiveChatUserPage extends KandyPage {


    public function render()
    {
        if(isset($_POST) && !empty($_POST)){
            if(isset($_POST['liveChatUsers'])){
                update_option('kandy_live_chat_users', json_encode($_POST['liveChatUsers']));
            }
        }
        $this->render_page_start("Users use for live chat");
        $excluded_users = get_option('kandy_excluded_users');
        $excluded_users = preg_split('/[\s,]+/',$excluded_users);
        $kandyLiveChatUsers = json_decode(get_option('kandy_live_chat_users', '[]'));
        ?>
        <h3>Select Kandy users to reserve for live chat</h3>
        <form method="post">
        <select class="select2" multiple name='liveChatUsers[]'>
        <?php
            foreach($excluded_users as $user) {
                ?>
                <option <?php echo (array_search($user,$kandyLiveChatUsers) !== false )?"selected":'' ?>><?php echo $user;?></option>
            <?php
            }
            ?>
        </select>
        <button class="button-primary" type="submit">Save changes</button>

        </form>
        <?php
        $this->render_page_end();
    }

}