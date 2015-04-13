<?php

class KandyShortcode {

    static function init() {
        // Register script.
        add_action('wp_enqueue_scripts', array(__CLASS__,'register_my_script' ));

        // Kandy video shortCode.
        add_shortcode('kandyVideoButton', array(__CLASS__,'kandy_video_button_shortcode_content'));
        add_shortcode('kandyVideo', array(__CLASS__,'kandy_video_shortcode_content'));

        // Kandy voice shortCode.
        add_shortcode('kandyVoiceButton', array(__CLASS__,'kandy_voice_shortcode_content'));

        // Kandy addressBook shortCode.
        add_shortcode('kandyStatus', array(__CLASS__,'kandy_status_shortcode_content'));
        add_shortcode('kandyAddressBook', array(__CLASS__,'kandy_addressBook_shortcode_content'));

        // Kandy chat shortCode.
        add_shortcode('kandyChat', array(__CLASS__,'kandy_chat_shortcode_content'));

        add_action('init', array(__CLASS__,'my_kandy_tinymce_button'));
        add_action('wp_logout', array(__CLASS__,'my_kandy_logout'));

        if(isset($_COOKIE['kandy_logout'])){
            KandyApi::kandyLogout($_COOKIE['kandy_logout']);
        }

        // Kandy Get User For Search Action
        add_action( 'wp_ajax_kandy_get_user_for_search', array(__CLASS__,'kandy_get_user_for_search_callback'));
        add_action( 'wp_ajax_kandy_get_name_for_contact', array(__CLASS__,'kandy_get_name_for_contact_callback'));
        add_action( 'wp_ajax_kandy_get_name_for_chat_content', array(__CLASS__,'kandy_get_name_for_chat_content_callback'));
    }

    /**
     * Get user for search callback
     */
    function kandy_get_user_for_search_callback() {

        $result = array();
        if(isset($_GET['q'])){
            $searchString = $_GET['q'];
            $userResults = get_users(array('search'=> $searchString));

            foreach ($userResults as $row) {
                $kandyUser = KandyApi::getAssignUser($row->ID);
                if($kandyUser) {
                    $kandyFullName = $kandyUser->user_id . "@" . $kandyUser->domain_name;

                    $userToAdd = array(
                        'id' => $kandyFullName,
                        'text' => $row->display_name
                    );
                    array_push($result, $userToAdd);
                }
            }
        }
        echo json_encode($result);
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /**
     * Kandy get name for contact
     */
    function kandy_get_name_for_contact_callback() {
        $contacts = array();
        if(isset($_GET['data'])) {
            $contacts = $_GET['data'];
            foreach ($contacts as &$contact) {
                $user = KandyApi::getUserByKandyUserMail($contact['contact_user_name']);
                if(!empty($user)) {
                    if($user == KANDY_UN_ASSIGN_USER) {
                        $displayName = KANDY_UN_ASSIGN_USER;
                    } else {
                        $displayName = $user->display_name;
                    }
                } else {
                    $displayName = "";
                }
                $contact['display_name'] = $displayName;
            }

        }

        echo json_encode($contacts);
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /**
     * Kandy Get Name for chat content ajax
     */
    public function kandy_get_name_for_chat_content_callback()
    {
        $messages = array();
        if(isset($_GET['data'])) {
            $messages = $_GET['data'];
            foreach ($messages as &$message) {
                if (!isset($message['sender'])) {
                    continue;
                }
                $sender = $message['sender'];
                $user = KandyApi::getUserByUserId($sender['user_id']);
                $displayName = "";
                if($user) {
                    $result = get_user_by('id', $user->main_user_id);
                    if($result) {
                        $displayName = $result->display_name;
                    }
                }
                $sender['display_name'] = $displayName;
                $sender['contact_user_name'] = $sender['full_user_id'];
                $message['sender'] = $sender;
            }
        }

        echo json_encode($messages);
        wp_die(); // this is required to terminate immediately and return a proper response
    }

    /**
     * Register script
     */
    static function register_my_script() {
        /*if(get_option('kandy_jquery_reload', "0")){
            wp_register_script('kandy_jquery', KANDY_JQUERY);
        }*/

        //register script
        $kandyJsUrl = get_option('kandy_js_url');
        if(empty($kandyJsUrl)){
            $kandyJsUrl = KANDY_JS_URL;
        }

        $kandyFcsUrl = get_option('kandy_fcs_url');
        if(empty($kandyFcsUrl)){
            $kandyFcsUrl = KANDY_FCS_URL;
        }
        wp_register_script(
            'kandy_js_url',
            $kandyJsUrl,
            array('jquery'),
            '4.1',
            true
        );
        wp_register_script(
            'kandy_fcs_url',
            $kandyFcsUrl,
            array('jquery'),
            '4.1',
            true
        );

        wp_register_script(
            'kandy_wordpress_js',
            KANDY_PLUGIN_URL . "/js/kandyWordpress.js",
            array(),
            KANDY_PLUGIN_VERSION,
            true
        );

        // in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
        wp_localize_script( 'kandy_wordpress_js', 'ajax_object',
            array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'we_value' => 1234 ) );

        wp_register_script(
            'kandy_addressbook_js',
            KANDY_PLUGIN_URL . "/js/shortcode/KandyAddressBook.js",
            array(),
            KANDY_PLUGIN_VERSION,
            true
        );
        wp_register_script(
            'kandy_chat_js',
            KANDY_PLUGIN_URL . "/js/shortcode/KandyChat.js",
            array(),
            KANDY_PLUGIN_VERSION,
            true
        );
        wp_register_script(
            'kandy_video_js',
            KANDY_PLUGIN_URL . "/js/shortcode/KandyVideo.js",
            array(),
            KANDY_PLUGIN_VERSION,
            true
        );
        wp_register_script(
            'kandy_voice_js',
            KANDY_PLUGIN_URL . "/js/shortcode/KandyVoice.js",
            array(),
            KANDY_PLUGIN_VERSION,
            true
        );
        //register style
        wp_register_style(
            'kandy_wordpress_css',
            KANDY_PLUGIN_URL . "/css/kandyWordpress.css",
            array(),
            '4.1'
        );
        wp_register_style(
            'kandy_addressbook_css',
            KANDY_PLUGIN_URL . "/css/shortcode/KandyAddressBook.css",
            array(),
            '4.1'
        );
        wp_register_style(
            'kandy_chat_css',
            KANDY_PLUGIN_URL . "/css/shortcode/KandyChat.css",
            array(),
            '4.1'
        );
        wp_register_style(
            'kandy_video_css',
            KANDY_PLUGIN_URL . "/css/shortcode/KandyVideo.css",
            array(),
            '4.1'
        );
        wp_register_style(
            'kandy_voice_css',
            KANDY_PLUGIN_URL . "/css/shortcode/KandyVoice.css",
            array(),
            '4.1'
        );
	}

    /**
     * Kandy Video Content
     * @param $attr
     * @return null|string
     */
    function kandy_video_shortcode_content($attr) {
        $output = "";
        if(!empty($attr)){
            $result = self::kandySetup();
            if($result['success']) {
                // init title attribute
                if(isset($attr['title'])){
                    $title = $attr['title'];
                } else {
                    $title = 'Kandy Video';
                }

                //init class attribute
                $class = 'kandyVideo ';
                if(isset($attr['class'])){
                    $class .= $attr['class'] ;
                }

                //init id attribute
                $id = 'kandy-video-'. rand() . ' ';
                if(isset($attr['id'])){
                    $id = $attr['id'] ;
                }

                //init htmlOptions
                $htmlOptionsAttributes = '';
                /*if (!isset($attr['style'])) {
                    $htmlOptionsAttributes = "style = 'width: 300px; height: 225px;background-color: darkslategray;'";
                }*/

                foreach ($attr as $key => $value) {
                    if ($key != "id" && $key != "class" && $key != "title") {
                        $htmlOptionsAttributes .= $key . "= '" . $value . "'";
                    }
                }

                $output = '<div class="'. $class .'">';
                $output .= '<p class="title">' . $title .'</p>';
                $output .= '<span class="video" id="' . $id .'"  '. $htmlOptionsAttributes.'></span>';
                $output .= '</div>';
            } else {
                $output = '<p>' . __('Can not setup kandy video. Please contact administrator') . '<p>';
            }

            if(isset($result['output'])){
                $output .= $result['output'];
            }
        }
        return $output;

    }

    /**
     * Kandy Video Button Content
     * @param $attr
     * @return null|string
     */
    function kandy_video_button_shortcode_content($attr) {
        $output = "";

        if(!empty($attr)){
            $result = self::kandySetup();
            if($result['success']) {

                wp_enqueue_script("kandy_video_js");
                wp_enqueue_style("kandy_video_css");
                //load script and css
                wp_enqueue_script("select-2-script", KANDY_PLUGIN_URL . '/js/select2-3.5.2/select2.js');
                wp_enqueue_style("select-2-style", KANDY_PLUGIN_URL . '/js/select2-3.5.2/select2.css');

                //init class attribute
                $class = 'kandyButton ';
                if(isset($attr['class'])){
                    $class .= $attr['class'] ;
                }

                //init id attribute
                $id = 'kandy-video-button'. rand() . ' ';
                if(isset($attr['id'])){
                    $id = $attr['id'] ;
                }

                $output = '<div class="'. $class .'" id ="' . $id .'">' .
                    '<div class="kandyButtonComponent kandyVideoButtonSomeonesCalling" id="incomingCall">' .
                    '<label>Incoming Call...</label>' .
                    '<input class="btmAnswerVideoCall" type="button" value="Answer" onclick="kandy_answerVideoCall(this)"/>'.
                    '</div>'.

                    '<div class="kandyButtonComponent kandyVideoButtonCallOut" id="callOut">'.
                    '<label>User to call</label>'.
                    '<input id="callOutUserId" type="text" value="" class="select2"/>'.
                    '<input class="btnCall" id="callBtn" type="button" value="Call" onclick="kandy_make_video_call(this)"/>'.
                    '</div>'.

                    '<div class="kandyButtonComponent kandyVideoButtonCalling" id="calling">' .
                    '<label>Calling...</label>' .
                    '<input type="button" class="btnEndCall" value="End Call" onclick="kandy_end_call(this)"/>' .
                    '</div>'.
                    '<div class="kandyButtonComponent kandyVideoButtonOnCall" id="onCall">' .
                    '<label>You are connected!</label>' .
                    '<input class="btnEndCall" type="button" value="End Call" onclick="kandy_end_call(this)"/>'.
                    '</div>'.
                    '</div>';

                if(isset($result['output'])){
                    $output .= $result['output'];
                }

            } else {
                $output = '<p>' . __('Can not setup kandy video button. Please contact administrator') . '<p>';
            }
        }
        return $output;
    }

    /**
     * Kandy Voice Button Content
     * @param $attr
     * @return null|string
     */
    function kandy_voice_shortcode_content($attr) {
        $output = "";
        if(!empty($attr)){
            $result = self::kandySetup();
            if($result['success']) {
                wp_enqueue_script("kandy_voice_js");
                wp_enqueue_style("kandy_voice_css");

                //load script and css
                wp_enqueue_script("select-2-script", KANDY_PLUGIN_URL . '/js/select2-3.5.2/select2.js');
                wp_enqueue_style("select-2-style", KANDY_PLUGIN_URL . '/js/select2-3.5.2/select2.css');

                //init class attribute
                $class = 'kandyButton ';
                if(isset($attr['class'])){
                    $class .= $attr['class'] ;
                }

                //init id attribute
                $id = 'kandy-voice-button'. rand() . ' ';
                if(isset($attr['id'])){
                    $id = $attr['id'] ;
                }

                //init incomingLabel attribute
                $incomingLabel = 'Incoming Call...';
                if(isset($attr['incomingLabel'])){
                    $incomingLabel = $attr['incomingLabel'] ;
                }

                //init incomingLabel attribute
                $incomingButtonText = 'Answer';
                if(isset($attr['incomingButtonText'])){
                    $incomingButtonText = $attr['incomingButtonText'] ;
                }

                //init callOutLabel attribute
                $callOutLabel = 'User to call';
                if(isset($attr['callOutLabel'])){
                    $callOutLabel = $attr['callOutLabel'] ;
                }

                //init callOutButtonText attribute
                $callOutButtonText = 'Call';
                if(isset($attr['callOutButtonText'])){
                    $callOutButtonText = $attr['callOutButtonText'] ;
                }

                //init callOutLabel attribute
                $callingLabel = 'Calling...';
                if(isset($attr['callingLabel'])){
                    $callingLabel = $attr['callingLabel'] ;
                }

                //init callOutButtonText attribute
                $callingButtonText = 'End Call';
                if(isset($attr['callingButtonText'])){
                    $callingButtonText = $attr['callingButtonText'] ;
                }

                //init callOutLabel attribute
                $onCallLabel = 'You are connected!';
                if(isset($attr['onCallLabel'])){
                    $onCallLabel = $attr['onCallLabel'] ;
                }

                //init callOutButtonText attribute
                $onCallButtonText = 'End Call';
                if(isset($attr['onCallButtonText'])){
                    $onCallButtonText = $attr['onCallButtonText'] ;
                }

                $output = '<div class="'. $class .'" id ="'. $id .'">' .
                    '<div class="kandyButtonComponent kandyVideoButtonSomeonesCalling" id="incomingCall">' .
                    '<label>'. $incomingLabel .'</label>' .
                    '<input class="btnAnswerVoiceCall" type="button" value="'. $incomingButtonText .'" onclick="kandy_answerVoiceCall(this)"/>'.
                    '</div>'.

                    '<div class="kandyButtonComponent kandyVideoButtonCallOut" id="callOut">'.
                    '<label>'. $callOutLabel .'</label>'.
                    '<input id="callOutUserId" type="text" value="" class="select2"/>'.
                    '<input class="btnCall" id="callBtn" type="button" value="'. $callOutButtonText .'" onclick="kandy_make_voice_call(this)"/>'.
                    '</div>'.

                    '<div class="kandyButtonComponent kandyVideoButtonCalling" id="calling">' .
                    '<label>'. $callingLabel .'</label>' .
                    '<input type="button" class="btnEndCall" value="'. $callingButtonText .'" onclick="kandy_end_call(this)"/>' .
                    '</div>'.
                    '<div class="kandyButtonComponent kandyVideoButtonOnCall" id="onCall">' .
                    '<label>'. $onCallLabel .'</label>' .
                    '<input class="btnEndCall" type="button" value=" '. $onCallButtonText .' " onclick="kandy_end_call(this)"/>'.
                    '</div>'.
                    '<div class="videoVoiceCallHolder">
                        <span class="video"></span>
                    </div>' .
                    '</div>';
                if(isset($result['output'])){
                    $output .= $result['output'];
                }
            } else {
                $output = '<p>' . __('Can not setup kandy voice button. Please contact administrator') . '<p>';
            }
        }
        return $output;
    }

    /**
     * Kandy Status shortcode content
     * @param $attr
     * @return string
     */
    function kandy_status_shortcode_content($attr) {
        $output = "";
        if(!empty($attr)){
            $result = self::kandySetup();
            if($result['success']) {

                // init title attribute
                if(isset($attr['title'])){
                    $title = $attr['title'];
                } else {
                    $title = 'My Status';
                }
                //init class attribute
                $class = 'kandyStatus ';
                if(isset($attr['class'])){
                    $class .= $attr['class'] ;
                }

                //init id attribute
                $id = 'kandy-status'. rand() . ' ';
                if(isset($attr['id'])){
                    $id = $attr['id'] ;
                }

                //init htmlOptions attribute
                $htmlOptionsAttributes = '';


                foreach ($attr as $key => $value) {
                    if ($key != "id" && $key != "class" && $key != "title") {
                        $htmlOptionsAttributes .= $key . "= '" . $value . "'";
                    }
                }
                $output = '<div class="'. $class .'">'.
                    '<span class="title"> ' . $title.' </span><select id="'. $id .'" class="dropDown" '. $htmlOptionsAttributes .' onchange="kandy_myStatusChanged($(this).val())">'.
                    '<option value="0" selected>Available</option>'.
                    '<option value="1">Unavailable</option>'.
                    '<option value="2">Away</option>'.
                    '<option value="3">Out To Lunch</option>'.
                    '<option value="4">Busy</option>'.
                    '<option value="5">On Vacation</option>'.
                    '<option value="6">Be Right Back</option>'.
                    '</select>'.
                    '</div>';
                if(isset($result['output'])){
                    $output .= $result['output'];
                }
            } else {
                $output = '<p>' . __('Can not setup kandy status. Please contact administrator') . '<p>';
            }
        }
        return $output;
    }

    /**
     * Kandy Presence
     * @param $attr
     * @return null|string
     */
    function kandy_addressBook_shortcode_content($attr) {
        $output = "";
        if(!empty($attr)){
            $result = self::kandySetup();
            if($result['success']) {
                wp_enqueue_script("kandy_addressbook_js");
                wp_enqueue_style("kandy_addressbook_css");

                //load script and css
                wp_enqueue_script("select-2-script", KANDY_PLUGIN_URL . '/js/select2-3.5.2/select2.js');
                wp_enqueue_style("select-2-style", KANDY_PLUGIN_URL . '/js/select2-3.5.2/select2.css');

                // init title attribute
                if(isset($attr['title'])){
                    $title = $attr['title'];
                } else {
                    $title = 'My Contact';
                }
                //init class attribute
                $class = 'kandyAddressBook ';
                if(isset($attr['class'])){
                    $class .= $attr['class'] ;
                }

                //init id attribute
                $id = 'kandy-address-book'. rand() . ' ';
                if(isset($attr['id'])){
                    $id = $attr['id'] ;
                }

                //init userLabel attribute
                $userLabel = 'User';
                if(isset($attr['userLabel'])){
                    $userLabel = $attr['userLabel'] ;
                }

                //init searchLabel attribute
                $searchLabel = 'Search';
                if(isset($attr['searchLabel'])){
                    $searchLabel = $attr['searchLabel'] ;
                }

                //init htmlOptions attribute
                $htmlOptionsAttributes = '';

                foreach ($attr as $key => $value) {
                    if ($key != "id" && $key != "class" && $key != "title") {
                        $htmlOptionsAttributes .= $key . "= '" . $value . "'";
                    }
                }
                $output = '<div class="'. $class.'" id="'. $id.'" '. $htmlOptionsAttributes .'>'.
                    '<div class="kandyAddressContactList">'.
                    '<div class="myContactsTitle"><p>'. $title.'</p></div>'.
                    '</div>
                    <div class="kandyDirectorySearch">'.$userLabel.':<input id="kandySearchUserName" class="select2" />
                    <input type="button" value="Add Contact" onclick="addContacts();"/>
                    </div>
                    ';
                if(isset($result['output'])){
                    $output .= $result['output'];
                }
            } else {
                $output = '<p>' . __('Can not setup kandy address book. Please contact administrator') . '<p>';
            }
        }
        return $output;
    }

    /**
     * Kandy Chat Content
     * @param $attr
     * @return null|string
     */
    function kandy_chat_shortcode_content($attr) {
        $output = "";
        if(!empty($attr)){
            $result = self::kandySetup();
            if($result['success']) {
                wp_enqueue_script("kandy_chat_js");
                wp_enqueue_style("kandy_chat_css");
                //init class attribute
                $class = 'kandyChat ';
                if(isset($attr['class'])){
                    $class .= $attr['class'] ;
                }

                //init id attribute
                $id = 'kandy-chat'. rand() . ' ';
                if(isset($attr['id'])){
                    $id = $attr['id'] ;
                }

                //init contacts label attribute
                $contactLabel = 'Contacts';
                if(isset($attr['contactLabel'])){
                    $contactLabel = $attr['contactLabel'] ;
                }

                //init htmlOptions attribute
                $htmlOptionsAttributes = '';

                foreach ($attr as $key => $value) {
                    if ($key != "id" && $key != "class" && $key != "title") {
                        $htmlOptionsAttributes .= $key . "= '" . $value . "'";
                    }
                }
                // get current kandy user
                $current_user = wp_get_current_user();
                $assignUser = KandyApi::getAssignUser($current_user->ID);
                if($assignUser) {
                    $output = '<div class="' . $class .' cd-tabs" id="'. $id .'" '. $htmlOptionsAttributes .' >'.
                        '<input type="hidden" class="kandy_current_username" value="'. $current_user->display_name .'"/>'.
                        '<div class="chat-heading">
                            <div class="contact-heading">
                            <label>'. $contactLabel .'</label>
                            <select onchange="kandy_contactFilterChanged($(this).val())">
                            <option value="all">All</option>
                            <option value="offline">Offline</option>
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                            <option value="away">Away</option>
                            <option value="out-to-lunch">Out To Lunch</option>
                            <option value="busy">Busy</option>
                            <option value="on-vacation">On Vacation</option>
                            <option value="be-right-back">Be Right Back</option>
                            </select>
                        </div>
                        <div class="chat-with-message">
                            Chatting with <span class="chat-friend-name"></span>
                        </div>
                        <div class="clear-fix"></div>
                    </div>'.
                        '<nav><ul class="cd-tabs-navigation"></ul></nav>'.
                        '<ul class="cd-tabs-content"></ul>'.
                        '<div style="clear: both;"></div>'.
                        '</div>';
                } else {
                    $output = 'Not found kandy user';
                }

                if(isset($result['output'])){
                    $output .= $result['output'];
                }

            } else {
                $output = '<p>' . __('Can not setup kandy video. Please contact administrator') . '<p>';
            }
        }
        return $output;
    }

    /**
     * Setup for shortcode
     * @return array
     */
    static function kandySetup(){

        $current_user = wp_get_current_user();
        $assignUser = KandyApi::getAssignUser($current_user->ID);

        if($assignUser){
            $userName = $assignUser->user_id;
            $password = $assignUser->password;
            $kandyApiKey = get_option('kandy_api_key', KANDY_API_KEY);
            if(get_option('kandy_jquery_reload', "0")){
                wp_enqueue_script('kandy_jquery');
            }
            wp_enqueue_script("kandy_js_url");
            wp_enqueue_script("kandy_fcs_url");

            $output ="<script>if (window.login == undefined){window.login = function() {
                        KandyAPI.Phone.login('" . $kandyApiKey . "', '" . $userName . "', '" . $password . "');
                    };
                }</script>";
            wp_enqueue_script("kandy_wordpress_js");
            wp_enqueue_style("kandy_wordpress_css");

            $result = array("success" => true, "message" => '', 'output' => $output);
        } else {
            $result = array("success" => false, "message" => 'Can not found kandy user', 'output' => '');
        }

        return $result;

    }

    /**
     * Register TinyMCE Editor Button
     * @param $buttons
     * @return mixed
     */
    function register_kandy_tinymce_button( $buttons ) {
        array_push( $buttons, "|", "kandyVideo" );
        array_push( $buttons, "|", "kandyVoice" );
        array_push( $buttons, "|", "kandyPresence" );
        array_push( $buttons, "|", "kandyChat" );
        return $buttons;
    }

    /**
     * Add TinyMCE Plugin
     * @param $plugin_array
     * @return mixed
     */
    function add_kandy_tinymce_plugin( $plugin_array ) {

        $plugin_array['kandyVideo'] = KANDY_PLUGIN_URL . '/js/tinymce/KandyVideo.js';
        $plugin_array['kandyVoice'] = KANDY_PLUGIN_URL . '/js/tinymce/KandyVoice.js';
        $plugin_array['kandyPresence'] = KANDY_PLUGIN_URL . '/js/tinymce/KandyPresence.js';
        $plugin_array['kandyChat'] = KANDY_PLUGIN_URL . '/js/tinymce/KandyChat.js';
        return $plugin_array;
    }

    /**
     * Register Kandy Tiny Button
     */
    function my_kandy_tinymce_button() {

        if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') ) {
            return;
        }

        if ( get_user_option('rich_editing') == 'true' ) {
            add_filter( 'mce_external_plugins', array(__CLASS__,'add_kandy_tinymce_plugin') );
            add_filter( 'mce_buttons', array(__CLASS__,'register_kandy_tinymce_button') );
        }

    }

    /**
     * Kandy Logout
     */
    function my_kandy_logout(){
        $current_user = wp_get_current_user();
        if($current_user) {
            setcookie( 'kandy_logout', $current_user->ID, time() + 3600);
        }
    }

    /**
     * Get option HTML for kandy user select
     * @param $userId
     * @return string
     */
    static   function getKandyUserOptionData(){
        $result = "";
        $userResults = get_users();

        foreach ($userResults as $row) {
            $kandyUser = KandyApi::getAssignUser($row->ID);
            if($kandyUser) {
                $kandyFullName = $kandyUser->user_id . "@" . $kandyUser->domain_name;
                $result .= "<option value ='" . $kandyFullName."'>". $row->display_name . "</option>";
            }
        }
        if(empty($result)) {
            $result .= "<option value =''>" . __('Please select assigned user') ."</option>";
        }
        return $result;

    }

    /**
     * Add script to active kandy user select 2
     * @param $elementId
     */
    static function activeSelect2($elementId){
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($){

                $("#<?php echo $elementId; ?>").select2();
            });
        </script>
    <?php
    }
}
