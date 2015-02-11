<?php

class KandyShortcode {

    static function init() {
        //register script
        add_action('wp_enqueue_scripts', array(__CLASS__,'register_my_script' ));

        //kandy video shortcode
        add_shortcode('kandyVideoButton', array(__CLASS__,'kandy_video_button_shortcode_content'));
        add_shortcode('kandyVideo', array(__CLASS__,'kandy_video_shortcode_content'));

        //kandy voice shortcode
        add_shortcode('kandyVoiceButton', array(__CLASS__,'kandy_voice_shortcode_content'));

        //kandy addressbook shortcode
        add_shortcode('kandyStatus', array(__CLASS__,'kandy_status_shortcode_content'));
        add_shortcode('kandyAddressBook', array(__CLASS__,'kandy_addressbook_shortcode_content'));

        //kandy chat shortcode
        add_shortcode('kandyChat', array(__CLASS__,'kandy_chat_shortcode_content'));

        add_action('init', array(__CLASS__,'my_kandy_tinymce_button'));
        add_action('wp_logout', array(__CLASS__,'my_kandy_logout'));

        if(isset($_COOKIE['kandy_logout'])){
            KandyApi::kandyLogout($_COOKIE['kandy_logout']);
        }
    }
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
     * @param $attrs
     * @return null|string
     */
    function kandy_video_shortcode_content($attrs) {
        $output = "";
        if(!empty($attrs)){
            $result = self::kandySetup();
            if($result['success']) {
                // init title attribute
                if(isset($attrs['title'])){
                    $title = $attrs['title'];
                } else {
                    $title = 'Kandy Video';
                }

                //init class attribute
                $class = 'kandyVideo ';
                if(isset($attrs['class'])){
                    $class .= $attrs['class'] ;
                }

                //init id attribute
                $id = 'kandy-video-'. rand() . ' ';
                if(isset($attrs['id'])){
                    $id = $attrs['id'] ;
                }

                //init htmlOptions
                $htmlOptionsAttributes = '';
                /*if (!isset($attrs['style'])) {
                    $htmlOptionsAttributes = "style = 'width: 300px; height: 225px;background-color: darkslategray;'";
                }*/

                foreach ($attrs as $key => $value) {
                    if ($key != "id" && $key != "class" && $key != "title") {
                        $htmlOptionsAttributes .= $key . "= '" . $value . "'";
                    }
                }

                $output = '<div class="'. $class .'">';
                $output .= '<p class="title">' . $title .'</p>';
                $output .= '<span class="video" id="' . $id .'"  '. $htmlOptionsAttributes.'></span>';
                $output .= '</div>';
            } else {
                $output = '<p>Can not setup kandy video...</p>';
            }

            if(isset($result['output'])){
                $output .= $result['output'];
            }
        }
        return $output;

    }

    /**
     * Kandy Video Button Content
     * @param $attrs
     * @return null|string
     */
    function kandy_video_button_shortcode_content($attrs) {
        if(!empty($attrs)){
            $result = self::kandySetup();
            if($result['success']) {

                wp_enqueue_script("kandy_video_js");
                wp_enqueue_style("kandy_video_css");

                //init class attribute
                $class = 'kandyButton ';
                if(isset($attrs['class'])){
                    $class .= $attrs['class'] ;
                }

                //init id attribute
                $id = 'kandy-video-button'. rand() . ' ';
                if(isset($attrs['id'])){
                    $id = $attrs['id'] ;
                }

                $output = '<div class="'. $class .'" id ="' . $id .'">' .
                    '<div class="kandyButtonComponent kandyVideoButtonSomeonesCalling" id="incomingCall">' .
                    '<label>Incoming Call...</label>' .
                    '<input class="btmAnswerVideoCall" type="button" value="Answer" onclick="kandy_answerVideoCall(this)"/>'.
                    '</div>'.

                    '<div class="kandyButtonComponent kandyVideoButtonCallOut" id="callOut">'.
                    '<label>User to call</label>'.
                    '<input id="callOutUserId" type="text" value=""/>'.
                    '<input class="btnCall" id="callBtn" type="button" value="Call" onclick="kandy_makeVideoCall(this)"/>'.
                    '</div>'.

                    '<div class="kandyButtonComponent kandyVideoButtonCalling" id="calling">' .
                    '<label>Calling...</label>' .
                    '<input type="button" class="btnEndCall" value="End Call" onclick="kandy_endCall(this)"/>' .
                    '</div>'.
                    '<div class="kandyButtonComponent kandyVideoButtonOnCall" id="onCall">' .
                    '<label>You are connected!</label>' .
                    '<input class="btnEndCall" type="button" value="End Call" onclick="kandy_endCall(this)"/>'.
                    '</div>'.
                    '</div>';

                if(isset($result['output'])){
                    $output .= $result['output'];
                }

            } else {
                $output = '<p>' . __('Can not setup kandy video button...') . '<p>';
            }
            return $output;
        }

    }

    /**
     * Kandy Voice Button Content
     * @param $attrs
     * @return null|string
     */
    function kandy_voice_shortcode_content($attrs) {

        if(!empty($attrs)){
            $result = self::kandySetup();
            if($result['success']) {
                wp_enqueue_script("kandy_voice_js");
                wp_enqueue_style("kandy_voice_css");
                //init class attribute
                $class = 'kandyButton ';
                if(isset($attrs['class'])){
                    $class .= $attrs['class'] ;
                }

                //init id attribute
                $id = 'kandy-voice-button'. rand() . ' ';
                if(isset($attrs['id'])){
                    $id = $attrs['id'] ;
                }

                //init incomingLabel attribute
                $incomingLabel = 'Incoming Call...';
                if(isset($attrs['incomingLabel'])){
                    $incomingLabel = $attrs['incomingLabel'] ;
                }

                //init incomingLabel attribute
                $incomingButtonText = 'Answer';
                if(isset($attrs['incomingButtonText'])){
                    $incomingButtonText = $attrs['incomingButtonText'] ;
                }

                //init callOutLabel attribute
                $callOutLabel = 'User to call';
                if(isset($attrs['callOutLabel'])){
                    $callOutLabel = $attrs['callOutLabel'] ;
                }

                //init callOutButtonText attribute
                $callOutButtonText = 'Call';
                if(isset($attrs['callOutButtonText'])){
                    $callOutButtonText = $attrs['callOutButtonText'] ;
                }

                //init callOutLabel attribute
                $callingLabel = 'Calling...';
                if(isset($attrs['callingLabel'])){
                    $callingLabel = $attrs['callingLabel'] ;
                }

                //init callOutButtonText attribute
                $callingButtonText = 'End Call';
                if(isset($attrs['callingButtonText'])){
                    $callingButtonText = $attrs['callingButtonText'] ;
                }

                //init callOutLabel attribute
                $onCallLabel = 'You are connected!';
                if(isset($attrs['onCallLabel'])){
                    $onCallLabel = $attrs['onCallLabel'] ;
                }

                //init callOutButtonText attribute
                $onCallButtonText = 'End Call';
                if(isset($attrs['onCallButtonText'])){
                    $onCallButtonText = $attrs['onCallButtonText'] ;
                }

                $output = '<div class="'. $class .'" id ="'. $id .'">' .
                    '<div class="kandyButtonComponent kandyVideoButtonSomeonesCalling" id="incomingCall">' .
                    '<label>'. $incomingLabel .'</label>' .
                    '<input class="btnAnswerVoiceCall" type="button" value="'. $incomingButtonText .'" onclick="kandy_answerVoiceCall(this)"/>'.
                    '</div>'.

                    '<div class="kandyButtonComponent kandyVideoButtonCallOut" id="callOut">'.
                    '<label>'. $callOutLabel .'</label>'.
                    '<input id="callOutUserId" type="text" value=""/>'.
                    '<input class="btnCall" id="callBtn" type="button" value="'. $callOutButtonText .'" onclick="kandy_makeVoiceCall(this)"/>'.
                    '</div>'.

                    '<div class="kandyButtonComponent kandyVideoButtonCalling" id="calling">' .
                    '<label>'. $callingLabel .'</label>' .
                    '<input type="button" class="btnEndCall" value="'. $callingButtonText .'" onclick="kandy_endCall(this)"/>' .
                    '</div>'.
                    '<div class="kandyButtonComponent kandyVideoButtonOnCall" id="onCall">' .
                    '<label>'. $onCallLabel .'</label>' .
                    '<input class="btnEndCall" type="button" value=" '. $onCallButtonText .' " onclick="kandy_endCall(this)"/>'.
                    '</div>'.
                    '<div class="videoVoiceCallHolder">
                        <span class="video"></span>
                    </div>' .
                    '</div>';
                if(isset($result['output'])){
                    $output .= $result['output'];
                }
            } else {
                $output = '<p>' . __('Can not setup kandy voice button...') . '<p>';
            }
            return $output;
        }

    }

    /**
     * Kandy Status shortcode content
     * @param $attrs
     * @return string
     */
    function kandy_status_shortcode_content($attrs) {

        if(!empty($attrs)){
            $result = self::kandySetup();
            if($result['success']) {

                // init title attribute
                if(isset($attrs['title'])){
                    $title = $attrs['title'];
                } else {
                    $title = 'My Status';
                }
                //init class attribute
                $class = 'kandyStatus ';
                if(isset($attrs['class'])){
                    $class .= $attrs['class'] ;
                }

                //init id attribute
                $id = 'kandy-status'. rand() . ' ';
                if(isset($attrs['id'])){
                    $id = $attrs['id'] ;
                }

                //init htmlOptions attribute
                $htmlOptionsAttributes = '';


                foreach ($attrs as $key => $value) {
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
                $output = '<p>' . __('Can not setup kandy status...') . '<p>';
            }
            return $output;
        }

    }

    /**
     * Kandy Presence
     * @param $attrs
     * @return null|string
     */
    function kandy_addressbook_shortcode_content($attrs) {

        if(!empty($attrs)){
            $result = self::kandySetup();
            if($result['success']) {
                wp_enqueue_script("kandy_addressbook_js");
                wp_enqueue_style("kandy_addressbook_css");

                // init title attribute
                if(isset($attrs['title'])){
                    $title = $attrs['title'];
                } else {
                    $title = 'My Contact';
                }
                //init class attribute
                $class = 'kandyAddressBook ';
                if(isset($attrs['class'])){
                    $class .= $attrs['class'] ;
                }

                //init id attribute
                $id = 'kandy-address-book'. rand() . ' ';
                if(isset($attrs['id'])){
                    $id = $attrs['id'] ;
                }

                //init id attribute
                $userLabel = 'User';
                if(isset($attrs['userLabel'])){
                    $userLabel = $attrs['userLabel'] ;
                }

                //init id attribute
                $searchLabel = 'Search';
                if(isset($attrs['searchLabel'])){
                    $searchLabel = $attrs['searchLabel'] ;
                }

                //init id attribute
                $searchResultLabel = 'Directory Search Results';
                if(isset($attrs['searchResultLabel'])){
                    $searchResultLabel = $attrs['searchResultLabel'] ;
                }

                //init htmlOptions attribute
                $htmlOptionsAttributes = '';

                foreach ($attrs as $key => $value) {
                    if ($key != "id" && $key != "class" && $key != "title") {
                        $htmlOptionsAttributes .= $key . "= '" . $value . "'";
                    }
                }
                $output = '<div class="'. $class.'" id="'. $id.'" '. $htmlOptionsAttributes .'>'.
                    '<div class="kandyAddressContactList">'.
                    '<div class="myContactsTitle"><p>'. $title.'</p></div>'.
                    '</div>'.

                    '<form class="kandyDirectorySearch" onsubmit="return false;">'.
                    $userLabel. ' : <input id="kandySearchUserName" type="text" value=""/>'.
                    '<input type="submit" value="'. $searchLabel .'" onclick="kandy_searchDirectoryByUserName();return false;"/>'.
                    '</form>'.

                    '<div class="kandyDirSearchResults" id="dirSearchResults">'.
                    '<div class="kandyDirSearchTitle">'. $searchResultLabel .'</div>'.
                    '</div>'.
                    '</div>';
                if(isset($result['output'])){
                    $output .= $result['output'];
                }
            } else {
                $output = '<p>' . __('Can not setup kandy address book...') . '<p>';
            }
            return $output;
        }

    }

    /**
     * Kandy Chat Content
     * @param $attrs
     * @return null|string
     */
    function kandy_chat_shortcode_content($attrs) {

        if(!empty($attrs)){
            $result = self::kandySetup();
            if($result['success']) {
                wp_enqueue_script("kandy_chat_js");
                wp_enqueue_style("kandy_chat_css");
                //init class attribute
                $class = 'kandyChat ';
                if(isset($attrs['class'])){
                    $class .= $attrs['class'] ;
                }

                //init id attribute
                $id = 'kandy-chat'. rand() . ' ';
                if(isset($attrs['id'])){
                    $id = $attrs['id'] ;
                }

                //init contacts label attribute
                $contactLabel = 'Contacts';
                if(isset($attrs['contactLabel'])){
                    $contactLabel = $attrs['contactLabel'] ;
                }

                //init htmlOptions attribute
                $htmlOptionsAttributes = '';

                foreach ($attrs as $key => $value) {
                    if ($key != "id" && $key != "class" && $key != "title") {
                        $htmlOptionsAttributes .= $key . "= '" . $value . "'";
                    }
                }
                // get current kandy user
                $current_user = wp_get_current_user();
                $assignUser = KandyApi::getAssignUser($current_user->ID);
                if($assignUser) {
                    $output = '<div class="' . $class .' cd-tabs" id="'. $id .'" '. $htmlOptionsAttributes .' >'.
                        '<input type="hidden" class="kandy_current_username" value="'. $assignUser->user_id .'"/>'.
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
                $output = '<p>' . __('Can not setup kandy video...') . '<p>';
            }
            return $output;
        }

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
    function my_kandy_logout(){
        $current_user = wp_get_current_user();
        if($current_user) {
            setcookie( 'kandy_logout', $current_user->ID, time() + 3600);
        }
    }
}




