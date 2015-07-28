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
        //Kandy coBrowisng shortcode
        add_shortcode('kandyCoBrowsing', array(__CLASS__,'kandy_cobrowsing_shortcode_content'));
        add_shortcode('kandySms', array(__CLASS__,'kandy_sms_shortcode_content'));	    //Kandy liveChat shortcode
	    add_shortcode('kandyLiveChat', array(__CLASS__, 'kandy_live_chat_shortcode_content'));

        add_action('init', array(__CLASS__,'my_kandy_tinymce_button'));
        add_action('wp_logout', array(__CLASS__,'my_kandy_logout'));

        if(isset($_COOKIE['kandy_logout'])){
            KandyApi::kandyLogout($_COOKIE['kandy_logout']);
        }

        // Kandy Get User For Search Action
        add_action( 'wp_ajax_kandy_get_user_for_search', array(__CLASS__,'kandy_get_user_for_search_callback'));
        add_action( 'wp_ajax_kandy_get_name_for_contact', array(__CLASS__,'kandy_get_name_for_contact_callback'));
        add_action( 'wp_ajax_kandy_get_name_for_chat_content', array(__CLASS__,'kandy_get_name_for_chat_content_callback'));
        add_action( 'wp_ajax_nopriv_kandy_register_guest', array(__CLASS__,'kandy_register_guest'));
        add_action( 'wp_ajax_nopriv_kandy_get_free_user', array(__CLASS__,'kandy_get_free_user'));
        add_action( 'wp_ajax_nopriv_kandy_end_chat_session', array(__CLASS__,'kandy_end_chat_session'));
        add_action( 'wp_ajax_nopriv_kandy_rate_agent', array(__CLASS__,'kandy_rate_agent'));
        add_action( 'wp_ajax_kandy_add_chat_agent', array(__CLASS__,'kandy_add_chat_agent'));

    }

    /**
     * Get user for search callback
     */
    function kandy_get_user_for_search_callback() {

        $result = array();
        if(isset($_GET['q'])){
            $searchString = $_GET['q'];
            $userResults = get_users(array('search'=> '*' . $searchString . '*'));

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
        if(isset($_POST['data'])) {
            $contacts = $_POST['data'];
            foreach ($contacts as &$contact) {
                if(isset($contact['contact_user_name'])) {
                    $user = KandyApi::getUserByKandyUserMail($contact['contact_user_name']);
                } else {
                    $user = KandyApi::getUserByKandyUserMail($contact['full_user_id']);
                }

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
        global $wpdb;
        $messages = array();
        if(isset($_POST['data'])) {
            $messages = $_POST['data'];
            foreach ($messages as &$message) {
                if (!isset($message['sender'])) {
                    continue;
                }
                $sender = $message['sender'];
                //if incoming message is from live chat users
                if(in_array($sender['user_id'], json_decode(get_option('kandy_live_chat_users')))){
                    $liveChatTable = $wpdb->prefix . 'kandy_live_chat';
                    $fakeEndTime = PHP_INT_MAX;
                    $user = $wpdb->get_results(
                        $sql = "SELECT customer_name, customer_email
                        FROM {$liveChatTable}
                        WHERE customer_user_id = '".$sender['full_user_id']."'
                        AND end_at = $fakeEndTime"
                    );
                    if($user){
                        $displayName = $user[0]->customer_name;
                        $sender['user_email'] = $user[0]->customer_email;
                    }
                } else {
                    $user = KandyApi::getUserByUserId($sender['user_id']);
                    $displayName = "";
                    if($user) {
                        $result = get_user_by('id', $user->main_user_id);
                        if($result) {
                            $displayName = $result->display_name;
                        }
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
            KANDY_PLUGIN_VERSION,
            true
        );
        wp_register_script(
            'kandy_fcs_url',
            $kandyFcsUrl,
            array('jquery'),
            KANDY_PLUGIN_VERSION,
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

	    wp_register_script(
		    'kandy_live_chat_js',
		    KANDY_PLUGIN_URL . "/js/kandylivechat.js",
		    array(),
		    KANDY_PLUGIN_VERSION,
		    true
	    );

	    wp_register_script(
			'kandy_rating_js',
			KANDY_PLUGIN_URL . "/js/jquery.rateit.min.js",
			array(),
			KANDY_PLUGIN_VERSION,
			true
	    );


        wp_register_script(
            'kandy_cobrowsing_js',
            KANDY_COBROWSING_JS,
            array('kandy_wordpress_js'),
            KANDY_PLUGIN_VERSION,
            true
        );        //register style
        wp_register_style(
            'kandy_wordpress_css',
            KANDY_PLUGIN_URL . "/css/kandyWordpress.css",
            array(),
            KANDY_PLUGIN_VERSION
        );
        wp_register_style(
            'kandy_addressbook_css',
            KANDY_PLUGIN_URL . "/css/shortcode/KandyAddressBook.css",
            array(),
            KANDY_PLUGIN_VERSION
        );
        wp_register_style(
            'kandy_chat_css',
            KANDY_PLUGIN_URL . "/css/shortcode/KandyChat.css",
            array(),
            KANDY_PLUGIN_VERSION
        );

        wp_register_style(
            'kandy_video_css',
            KANDY_PLUGIN_URL . "/css/shortcode/KandyVideo.css",
            array(),
            KANDY_PLUGIN_VERSION
        );
        wp_register_style(
            'kandy_voice_css',
            KANDY_PLUGIN_URL . "/css/shortcode/KandyVoice.css",
            array(),
            KANDY_PLUGIN_VERSION
        );
	    wp_register_style(
		    'kandy_live_chat_css',
		    KANDY_PLUGIN_URL . "/css/kandylivechat.css",
		    array(),
		    KANDY_PLUGIN_VERSION
	    );
	    wp_register_style(
		    'kandy_rating_css',
		    KANDY_PLUGIN_URL . "/css/rateit.css",
		    array(),
		    KANDY_PLUGIN_VERSION
	    );

	    // Pace lib.
	    wp_enqueue_script( "kandy-pace-script",
		    'https://cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/pace.min.js',
		    array( 'jquery' ), false, true );
	    wp_enqueue_style( "kandy-pace-style",
		    'https://cdnjs.cloudflare.com/ajax/libs/pace/1.0.2/themes/pink/pace-theme-minimal.css' );
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

                // Init class attribute.
                $class = 'kandyButton ';
                if (isset($attr['class']))
                {
                    $class .= $attr['class'];
                }

                // Init id attribute.
                $id = 'kandy-video-button' . rand();
                if (isset($attr['id']))
                {
                    $id = $attr['id'];
                }

                // Init incominglabel attribute.
                $incomingLabel = 'Incoming Call...';
                if (isset($attr['incominglabel'])) {
                    $incomingLabel = ($attr['incominglabel']);
                }

                // Init incomingbuttontext attribute.
                $incomingButtonText = 'Answer';
                if (isset($attr['incomingbuttontext'])) {
                    $incomingButtonText = ($attr['incomingbuttontext']);
                }

                // Init rejectbuttontext attribute.
                $rejectButtonText = 'Reject';
                if (isset($attr['rejectbuttontext'])) {
                    $incomingButtonText = ($attr['rejectbuttontext']);
                }

                // Init calloutlabel attribute.
                $callOutLabel = 'User to call';
                if (isset($attr['calloutlabel'])) {
                    $callOutLabel = ($attr['calloutlabel']);
                }

                // Init calloutbuttontext attribute.
                $callOutButtonText = 'Call';
                if (isset($attr['calloutbuttontext'])) {
                    $callOutButtonText = ($attr['calloutbuttontext']);
                }

                // Init callinglabel attribute.
                $callingLabel = 'Calling...';
                if (isset($attr['callinglabel'])) {
                    $callingLabel = ($attr['callinglabel']);
                }

                // Init callingbuttontext attribute.
                $callingButtonText = 'End Call';
                if (isset($attr['callingbuttontext'])) {
                    $callingButtonText = ($attr['callingbuttontext']);
                }

                // Init oncalllabel attribute.
                $onCallLabel = 'You are connected!';
                if (isset($attr['oncalllabel'])) {
                    $onCallLabel = $attr['oncalllabel'];
                }

                // Init oncallbuttontext attribute.
                $onCallButtonText = 'End Call';
                if (isset($attr['oncallbuttontext'])) {
                    $onCallButtonText = ($attr['oncallbuttontext']);
                }

                // Init $holdCallButtonText attribute.
                $holdCallButtonText = 'Hold Call';
                if (isset($attr['holdcallbuttontext'])) {
                    $holdCallButtonText = ($attr['holdcallbuttontext']);
                }

                // Init $resumeCallButtonText attribute.
                $resumeCallButtonText = 'Resume Call';
                if (isset($attr['resumecallbuttontext'])) {
                    $resumeCallButtonText = ($attr['resumecallbuttontext']);
                }

                $ajaxUserSearchUrl = admin_url( 'admin-ajax.php' );

                $output = '<div class="' . $class . '" id ="' . $id . '" data-call-id="0">' .
                    '<div class="kandyButtonComponent kandyVideoButtonSomeonesCalling" id="'.$id.'-incomingCall">' .
                    '<label>' . $incomingLabel . '</label>' .
                    '<input data-container="'.$id.'" class="btmAnswerVideoCall" type="button" value="' . $incomingButtonText . '" onclick="kandy_answer_video_call(this)"/>' .
                    '<input style="visibility: hidden" class="btmAnswerRejectCall" type="button" onclick="kandy_reject_video_call(this)" value="' . $rejectButtonText . '"/>' .
                    '</div>' .

                    '<div class="kandyButtonComponent kandyVideoButtonCallOut" id="'.$id.'-callOut">' .
                    '<label>' . $callOutLabel . '</label><input id="'.$id.'-callOutUserId" data-ajax-url="' . $ajaxUserSearchUrl . '" type="text" value="" class="select2"/>' .
                    '<input data-container="'.$id.'"  class="btnCall" id="callBtn" type="button" value="' . $callOutButtonText . '" onclick="kandy_make_video_call(this)"/>' .
                    '</div>' .

                    '<div class="kandyButtonComponent kandyVideoButtonCalling" id="'.$id.'-calling">' .
                    '<label>' . $callingLabel . '</label>' .
                    '<input data-container="'.$id.'"  type="button" class="btnEndCall" value="' . $callingButtonText . '" onclick="kandy_end_call(this)"/>' .
                    '</div>' .
                    '<div class="kandyButtonComponent kandyVideoButtonOnCall" id="'.$id.'-onCall">' .
                    '<label>' . $onCallLabel . '</label>' .
                    '<input data-container="'.$id.'"  class="btnEndCall" type="button" value="' . $onCallButtonText . '" onclick="kandy_end_call(this)"/>' .
                    '<input style="visibility: hidden" class="btnHoldCall" type="button" value="' . $holdCallButtonText . '" onclick="kandy_hold_call(this)"/>' .
                    '<input style="visibility: hidden" class="btnResumeCall" type="button" value="' . $resumeCallButtonText . '" onclick="kandy_resume_call(this)"/>' .
                    '</div></div>';

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

                // Init incominglabel attribute.
                $callType = '';
                if (isset($attr['type'])) {
                    $callType = ($attr['type']);
                }

                // Init incominglabel attribute.
                $callTo = '';
                if (isset($attr['callto'])) {
                    $callTo = ($attr['callto']);
                }

                //init class attribute
                $class = 'kandyButton ';
                if(isset($attr['class'])){
                    $class .= $attr['class'] ;
                }

                //init id attribute
                $id = 'kandy-voice-button'. rand();
                if(isset($attr['id'])){
                    $id = $attr['id'] ;
                }

                // Init incominglabel attribute.
                $incomingLabel = 'Incoming Call...';
                if (isset($attr['incominglabel'])) {
                    $incomingLabel = ($attr['incominglabel']);
                }

                // Init incomingbuttontext attribute.
                $incomingButtonText = 'Answer';
                if (isset($attr['incomingbuttontext'])) {
                    $incomingButtonText = ($attr['incomingbuttontext']);
                }

                // Init calloutlabel attribute.
                $callOutLabel = 'User to call';
                if (isset($attr['calloutlabel'])) {
                    $callOutLabel = ($attr['calloutlabel']);
                }

                // Init calloutbuttontext attribute.
                $callOutButtonText = 'Call';
                if (isset($attr['calloutbuttontext'])) {
                    $callOutButtonText = ($attr['calloutbuttontext']);
                }

                // Init callinglabel attribute.
                $callingLabel = 'Calling...';
                if (isset($attr['callinglabel'])) {
                    $callingLabel = ($attr['callinglabel']);
                }

                // Init callingbuttontext attribute.
                $callingButtonText = 'End Call';
                if (isset($attr['callingbuttontext'])) {
                    $callingButtonText = ($attr['callingbuttontext']);
                }

                // Init oncalllabel attribute.
                $onCallLabel = 'You are connected!';
                if (isset($attr['oncalllabel'])) {
                    $onCallLabel = $attr['oncalllabel'];
                }

                // Init oncallbuttontext attribute.
                $onCallButtonText = 'End Call';
                if (isset($attr['oncallbuttontext'])) {
                    $onCallButtonText = ($attr['oncallbuttontext']);
                }

                // Init holdcallbuttontext attribute.
                $holdCallButtonText = 'Hold Call';
                if (isset($attr['holdcallbuttontext'])) {
                    $holdCallButtonText = ($attr['holdcallbuttontext']);
                }

                // Init resumecallbuttontext attribute.
                $resumeCallButtonText = 'Resume Call';
                if (isset($attr['resumecallbuttontext'])) {
                    $resumeCallButtonText = ($attr['resumecallbuttontext']);
                }

                // Init rejectbuttontext attribute.
                $rejectButtonText = 'Reject';
                if (isset($attr['rejectbuttontext'])) {
                    $incomingButtonText = ($attr['rejectbuttontext']);
                }

                $ajaxUserSearchUrl = admin_url( 'admin-ajax.php' );
                if(strtoupper($callType) == KANDY_PSTN_TYPE) {

                    if (!isset($attr['calloutlabel'])) {
                        $callOutLabel = 'Enter Number';
                    }

                    if(empty($callTo)) {

                        $output = '<div class="' . $class . '" id ="' . $id . '" data-call-id="">' .
                            '<div class="kandyButtonComponent kandyVideoButtonSomeonesCalling" id="'.$id.'-incomingCall">' .
                            '<label>' . $incomingLabel . '</label>' .
                            '<input data-container="'.$id.'" class="btnAnswerVoiceCall" type="button" value="' . $incomingButtonText . '" onclick="kandy_answerVoiceCall(this)"/>' .
                            '<input data-container="'.$id.'" style="visibility: hidden" class="btmAnswerRejectCall" type="button" onclick="kandy_reject_video_call(this)" value="' . $rejectButtonText . '"/>' .
                            '</div>' .

                            '<div class="kandyButtonComponent kandyVideoButtonCallOut" id="'.$id.'-callOut">' .
                            '<label>' . $callOutLabel . '</label>' .
                            '<input id="'.$id.'-callOutUserId" style="display:block;" type="text" value=""/>' .
                            '<input data-container="'.$id.'" class="btnCall" id="callBtn" type="button" value="' . $callOutButtonText . '" onclick="kandy_make_pstn_call(this)"/>' .
                            '</div>' .

                            '<div class="kandyButtonComponent kandyVideoButtonCalling" id="'.$id.'-calling">' .
                            '<label>' . $callingLabel . '</label>' .
                            '<input data-container="'.$id.'" type="button" class="btnEndCall" value="' . $callingButtonText . '" onclick="kandy_end_call(this)"/>' .
                            '</div>' .
                            '<div class="kandyButtonComponent kandyVideoButtonOnCall" id="'.$id.'-onCall">' .
                            '<label>' . $onCallLabel . '</label>' .
                            '<input data-container="'.$id.'" class="btnEndCall" type="button" value=" ' . $onCallButtonText . ' " onclick="kandy_end_call(this)"/>' .
                            '<input data-container="'.$id.'" style="visibility: hidden" class="btnHoldCall" type="button" value="' . $holdCallButtonText . '" onclick="kandy_hold_call(this)"/>' .
                            '<input data-container="'.$id.'" style="visibility: hidden" class="btnResumeCall" type="button" value="' . $resumeCallButtonText . '" onclick="kandy_resume_call(this)"/>' .
                            '</div><div class="videoVoiceCallHolder"><div id="theirVideo" class="video"></div></div></div>';
                    } else {
                        $output = '<div class="' . $class . '" id ="' . $id . '" data-call-id="">' .
                            '<div class="kandyButtonComponent kandyVideoButtonSomeonesCalling" id="'.$id.'-incomingCall">' .
                            '<label>' . $incomingLabel . '</label>' .
                            '<input data-container="'.$id.'" class="btnAnswerVoiceCall" type="button" value="' . $incomingButtonText . '" onclick="kandy_answerVoiceCall(this)"/>' .
                            '<input data-container="'.$id.'" style="visibility: hidden" class="btmAnswerRejectCall" type="button" onclick="kandy_reject_video_call(this)" value="' . $rejectButtonText . '"/>' .
                            '</div>' .

                            '<div class="kandyButtonComponent kandyVideoButtonCallOut" id="'.$id.'-callOut">' .
                            '<input id="'.$id.'-callOutUserId" type="text" value ="'. $callTo .'"/>' .
                            '<input data-container="'.$id.'" class="btnCall" id="callBtn" type="button" value="' . $callOutButtonText . '" onclick="kandy_make_pstn_call(this)"/>' .
                            '</div>' .

                            '<div class="kandyButtonComponent kandyVideoButtonCalling" id="'.$id.'-calling">' .
                            '<label>' . $callingLabel . '</label>' .
                            '<input data-container="'.$id.'" type="button" class="btnEndCall" value="' . $callingButtonText . '" onclick="kandy_end_call(this)"/>' .
                            '</div>' .
                            '<div class="kandyButtonComponent kandyVideoButtonOnCall" id="'.$id.'-onCall">' .
                            '<label>' . $onCallLabel . '</label>' .
                            '<input data-container="'.$id.'" class="btnEndCall" type="button" value=" ' . $onCallButtonText . ' " onclick="kandy_end_call(this)"/>' .
                            '<input data-container="'.$id.'" style="visibility: hidden" class="btnHoldCall" type="button" value="' . $holdCallButtonText . '" onclick="kandy_hold_call(this)"/>' .
                            '<input data-container="'.$id.'" style="visibility: hidden" class="btnResumeCall" type="button" value="' . $resumeCallButtonText . '" onclick="kandy_resume_call(this)"/>'.
                            '</div><div class="videoVoiceCallHolder"><div id="theirVideo" class="video"></div></div></div>';
                    }

                } else {
                    if(empty($callTo)) {
                        $output = '<div class="' . $class . '" id ="' . $id . '" data-call-id="">' .
                            '<div class="kandyButtonComponent kandyVideoButtonSomeonesCalling" id="'.$id.'-incomingCall">' .
                            '<label>' . $incomingLabel . '</label>' .
                            '<input data-container="'.$id.'"  class="btnAnswerVoiceCall" type="button" value="' . $incomingButtonText . '" onclick="kandy_answerVoiceCall(this)"/>' .
                            '<input data-container="'.$id.'"  style="visibility: hidden" class="btmAnswerRejectCall" type="button" onclick="kandy_reject_video_call(this)" value="' . $rejectButtonText . '"/>' .
                            '</div>' .

                            '<div class="kandyButtonComponent kandyVideoButtonCallOut" id="'.$id.'-callOut">' .
                            '<label>' . $callOutLabel . '</label>' .
                            '<input id="'.$id.'-callOutUserId" data-ajax-url="' . $ajaxUserSearchUrl . '" type="text" value="" class="select2"/>' .
                            '<input data-container="'.$id.'" class="btnCall" id="callBtn" type="button" value="' . $callOutButtonText . '" onclick="kandy_makeVoiceCall(this)"/>' .
                            '</div>' .

                            '<div class="kandyButtonComponent kandyVideoButtonCalling" id="'.$id.'-calling">' .
                            '<label>' . $callingLabel . '</label>' .
                            '<input data-container="'.$id.'"  type="button" class="btnEndCall" value="' . $callingButtonText . '" onclick="kandy_end_call(this)"/>' .
                            '</div>' .
                            '<div class="kandyButtonComponent kandyVideoButtonOnCall" id="'.$id.'-onCall">' .
                            '<label>' . $onCallLabel . '</label>' .
                            '<input data-container="'.$id.'"  class="btnEndCall" type="button" value=" ' . $onCallButtonText . ' " onclick="kandy_end_call(this)"/>' .
                            '<input data-container="'.$id.'"  style="visibility: hidden" class="btnHoldCall" type="button" value="' . $holdCallButtonText . '" onclick="kandy_hold_call(this)"/>' .
                            '<input data-container="'.$id.'"  style="visibility: hidden" class="btnResumeCall" type="button" value="' . $resumeCallButtonText . '" onclick="kandy_resume_call(this)"/>' .
                            '</div><div class="videoVoiceCallHolder"><div id="theirVideo" class="video"></div></div></div>';
                    } else {
                        $output = '<div class="' . $class . '" id ="' . $id . '" data-call-id="">' .
                            '<div class="kandyButtonComponent kandyVideoButtonSomeonesCalling" id="'.$id.'-incomingCall">' .
                            '<label>' . $incomingLabel . '</label>' .
                            '<input data-container="'.$id.'"  class="btnAnswerVoiceCall" type="button" value="' . $incomingButtonText . '" onclick="kandy_answerVoiceCall(this)"/>' .
                            '<input data-container="'.$id.'"  style="visibility: hidden" class="btmAnswerRejectCall" type="button" onclick="kandy_reject_video_call(this)" value="' . $rejectButtonText . '"/>' .
                            '</div>' .

                            '<div class="kandyButtonComponent kandyVideoButtonCallOut" id="'.$id.'-callOut">' .
                            '<input id="'.$id.'-callOutUserId" type="text" value ="'. $callTo .'"/>' .
                            '<input data-container="'.$id.'"  class="btnCall" id="callBtn" type="button" value="' . $callOutButtonText . '" onclick="kandy_make_pstn_call(this)"/>' .
                            '</div>' .

                            '<div class="kandyButtonComponent kandyVideoButtonCalling" id="'.$id.'-calling">' .
                            '<label>' . $callingLabel . '</label>' .
                            '<input data-container="'.$id.'"  type="button" class="btnEndCall" value="' . $callingButtonText . '" onclick="kandy_end_call(this)"/>' .
                            '</div>' .
                            '<div class="kandyButtonComponent kandyVideoButtonOnCall" id="'.$id.'-onCall">' .
                            '<label>' . $onCallLabel . '</label>' .
                            '<input data-container="'.$id.'"  class="btnEndCall" type="button" value=" ' . $onCallButtonText . ' " onclick="kandy_end_call(this)"/>' .
                            '<input data-container="'.$id.'"  style="visibility: hidden" class="btnHoldCall" type="button" value="' . $holdCallButtonText . '" onclick="kandy_hold_call(this)"/>' .
                            '<input data-container="'.$id.'"  style="visibility: hidden" class="btnResumeCall" type="button" value="' . $resumeCallButtonText . '" onclick="kandy_resume_call(this)"/>'.
                            '</div><div class="videoVoiceCallHolder"><div id="theirVideo" class="video"></div></div></div>';
                    }
                }
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
     * Kandy Status shortcode content.
     * @param $attr
     * @return string
     */
    function kandy_status_shortcode_content($attr) {
        $output = "";
        if(!empty($attr)){
            $result = self::kandySetup();
            if($result['success']) {

                // Init title attribute.
                if(isset($attr['title'])){
                    $title = $attr['title'];
                } else {
                    $title = 'My Status';
                }
                // Init class attribute.
                $class = 'kandyStatus ';
                if(isset($attr['class'])){
                    $class .= $attr['class'] ;
                }

                // Init id attribute.
                $id = 'kandy-status'. rand() . ' ';
                if(isset($attr['id'])){
                    $id = $attr['id'] ;
                }

                // Init htmlOptions attribute.
                $htmlOptionsAttributes = '';


                foreach ($attr as $key => $value) {
                    if ($key != "id" && $key != "class" && $key != "title") {
                        $htmlOptionsAttributes .= $key . "= '" . $value . "'";
                    }
                }
                $output = '<div class="'. $class .'">'.
                    '<span class="title"> ' . $title.' </span><select id="'. $id .'" class="dropDown" '. $htmlOptionsAttributes .' onchange="kandy_myStatusChanged(jQuery(this).val())">'.
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
     * Kandy Presence.
     *
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

                // Load script and css.
                wp_enqueue_script("select-2-script", KANDY_PLUGIN_URL . '/js/select2-3.5.2/select2.js');
                wp_enqueue_style("select-2-style", KANDY_PLUGIN_URL . '/js/select2-3.5.2/select2.css');

                // Init title attribute.
                if(isset($attr['title'])){
                    $title = $attr['title'];
                } else {
                    $title = 'My Contact';
                }
                // Init class attribute.
                $class = 'kandyAddressBook ';
                if(isset($attr['class'])){
                    $class .= $attr['class'] ;
                }

                // Init id attribute.
                $id = 'kandy-address-book'. rand() . ' ';
                if(isset($attr['id'])){
                    $id = $attr['id'] ;
                }

                // Init userlabel attribute.
                $userLabel = 'User';
                if(isset($attr['userlabel'])){
                    $userLabel = $attr['userlabel'] ;
                }

                // Init searchlabel attribute.
                $addContactLabel = 'Add Contact';
                if(isset($attr['addcontactlabel'])){
                    $addContactLabel = $attr['addcontactlabel'] ;
                }

                // Init htmlOptions attribute.
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
                    <input type="button" value="' . $addContactLabel . '" onclick="addContacts();"/>
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
     * Kandy Chat Content.
     *
     * @param $attr
     * @return null|string
     */
    function kandy_chat_shortcode_content($attr) {
        $output = "";
        if(!empty($attr)){
            $result = self::kandySetup();
            if($result['success']) {
                global $wp_scripts;
                wp_enqueue_script("kandy_chat_js");
                wp_enqueue_style("kandy_chat_css");

                wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css');
                wp_enqueue_script( 'jquery-ui-core' );
                wp_enqueue_script( 'jquery-ui-dialog' );

                // get registered script object for jquery-ui
                $ui = $wp_scripts->query('jquery-ui-core');

                // tell WordPress to load the Smoothness theme from Google CDN
                $protocol = is_ssl() ? 'https' : 'http';
                $url = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.min.css";
                wp_enqueue_style('jquery-ui-smoothness', $url, false, null);
                //init class attribute
                $class = 'kandyChat ';
                if(isset($attr['class'])){
                    $class .= $attr['class'] ;
                }

                // Init id attribute.
                $id = 'kandy-chat'. rand() . ' ';
                if(isset($attr['id'])){
                    $id = $attr['id'] ;
                }

                // Init contactlabel label attribute.
                $contactLabel = 'Contacts';
                if(isset($attr['contactlabel'])){
                    $contactLabel = $attr['contactlabel'] ;
                }

                // Init htmlOptions attribute.
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
                        '<input type="hidden" class="kandy_user" value="'. $assignUser->user_id . '@' . $assignUser->domain_name .'"/>'.
                        '<div class="chat-heading">
                            <div class="contact-heading">
                            <label>'. $contactLabel .'</label>
                            <select onchange="kandy_contactFilterChanged(jQuery(this).val())">
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
                        <button id="btn-create-group-modal" class="chat-create-group">Create group</button>
                        <div class="clear-fix"></div>
                    </div>'.
                        '<nav>
                            <ul class="cd-tabs-navigation contacts"></ul>
                            <div class="separator hide"><span>Groups</span></div>
                            <ul class="cd-tabs-navigation groups"></ul>
                        </nav>'.
                        '<ul class="cd-tabs-content"></ul>'.
                        '<div style="clear: both;"></div>';


                    $output .= '<div id="kandy-chat-create-group-modal" title="Create a group">
                                    <label for="right-label" class="right inline">Group name</label>
                                    <input type="text" id="kandy-chat-create-session-name" placeholder="Group name">
                                </div></div>';
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

    public function kandy_sms_shortcode_content($attr)
    {
        $output = "";
        $defaults = array(
            "class"                 => "",
            'message_place_holder'  => "Your message",
            'number_place_holder'   => "Phone number",
            "btn_send_id"           => "btnSendSms",
            "btn_send_label"        => "Send Sms"
        );
        $params = shortcode_atts($defaults, $attr);
        if(!empty($attr)) {
            $result = self::kandySetup();
            if($result['success']){
                $current_user = wp_get_current_user();
                $assignUser = KandyApi::getAssignUser($current_user->ID);
                wp_localize_script('kandy_wordpress_js', 'sms', $params);
                if($assignUser){
                    $output = "<div class=\"{$params['class']} smsContainer \">
                        <div class=\"msgContainer\">
                            <textarea placeholder=\"{$params['message_place_holder']}\" name=\"msg\" id=\"msg\" cols=\"30\" rows=\"10\"></textarea>
                        </div>
                        <div class=\"numberContainer\">
                            <input type=\"text\" placeholder=\"{$params['number_place_holder']}\" name=\"phoneNum\" id=\"phoneNum\">
                        </div>
                        <button id=\"{$params['btn_send_id']}\">{$params['btn_send_label']}</button>
                        <!-- end oncall -->
                    </div>";
                }
                if(isset($result['output'])){
                    $output .= $result['output'];
                }
            }

        }
        return $output;
    }

    /**
     * Kandy cobrowsing shortcode
     */

    public function kandy_cobrowsing_shortcode_content($attr)
    {
        global $wp_scripts;
        $current_user = wp_get_current_user();
        $assignUser = KandyApi::getAssignUser($current_user->ID);
        $output = "";
        $defaults = array(
            'holder_id'                  => 'cobrowsing-holder',
            'btn_terminate_id'            => 'btnTerminateSession',
            'btn_stop_id'                 => 'btnStopCoBrowsing',
            'btn_leave_id'                => 'btnLeaveSession',
            'btn_start_browsing_viewer_id'  => 'btnStartCoBrowsingViewer',
            'btn_start_cobrowsing_id'      => 'btnStartCoBrowsing',
            'btn_connect_session_id'       => 'btnConnectSession',
            'current_user'               => $assignUser,
            'session_list_id'             => 'openSessions'
        );

        $params = shortcode_atts($defaults,$attr);

        if(!empty($params)){
            $result = self::kandySetup();
            // get current kandy user
            if($result['success']){
                if($assignUser) {
                    $output = "";
                    wp_enqueue_script('kandy_cobrowsing_js');
                    wp_enqueue_script( 'jquery-ui-core' );
                    wp_enqueue_script( 'jquery-ui-dialog' );

                    // get registered script object for jquery-ui
                    $ui = $wp_scripts->query('jquery-ui-core');

                    // tell WordPress to load the Smoothness theme from Google CDN
                    $protocol = is_ssl() ? 'https' : 'http';
                    $url = "$protocol://ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.min.css";
                    wp_enqueue_style('jquery-ui-smoothness', $url, false, null);
                    wp_enqueue_script('kandy_cobrowsing_function', KANDY_PLUGIN_URL . '/js/kandyCoBrowsing.js', 'kandy_wordpress_js');
                    wp_localize_script('kandy_cobrowsing_function','cobrowsing',$params);
                    $output = "
                <div id=\"coBrowsing\">
                    <button class=\"small tiny\" id=\"btnCreateSession\">Create Session</button>
                    <div>
                        <div class=\"openSessionWrap\">
                            <label>Available sessions</label>
                            <select id=\"{$params['session_list_id']}\"></select>
                        </div>
                        <div class=\"buttons\">
                            <button class=\"small\" title='join session' id=\"{$params['btn_connect_session_id']}\">Connect</button>
                            <button class=\"small\" title='Terminate session' id=\"{$params['btn_terminate_id']}\">Terminate</button>
                            <button class=\"small\" title='Start co-browsing' id=\"{$params['btn_start_cobrowsing_id']}\">Start</button>
                            <button class=\"small\" title='Start co-browsing viewer' id=\"{$params['btn_start_browsing_viewer_id']}\">Start viewer</button>
                            <button class=\"small\" title='Stop co-browsing' id=\"{$params['btn_stop_id']}\">Stop</button>
                            <button class=\"small\" title='Leave session' id=\"{$params['btn_leave_id']}\">Leave</button>
                        </div>
                    </div>
                    <div id=\"{$params['holder_id']}\"></div>
                </div>
                <div id=\"kandy-chat-create-group-modal\" title=\"Create a session\">
                    <label for=\"right-label\" class=\"right inline\">Session name</label>
                    <input type=\"text\" id=\"kandy-chat-create-session-name\" placeholder=\"Session name\">
                </div>";

                }
                if(isset($result['output'])){
                    $output .= $result['output'];
                }

            } else {
                $output = '<p>' . __('Could not setup Kandy CoBrowsing. Please contact administrator') . '<p>';
            }
        }
        return $output;
    }

    /**
     * @param $attr
     */
    function kandy_live_chat_shortcode_content($attr) {
		if(!empty($attr)){
            if(get_option('kandy_jquery_reload', "0")){
                wp_enqueue_script('kandy_jquery');
            }
            wp_enqueue_script("kandy_fcs_url");
            wp_enqueue_script("kandy_js_url");
            wp_enqueue_script('kandy_rating_js');
            wp_enqueue_script('kandy_live_chat_js');
            wp_enqueue_style('kandy_live_chat_css');
            wp_enqueue_style('kandy_rating_css');
            wp_localize_script('kandy_live_chat_js', 'ajax_object', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ), 'we_value' => 1234 )
            );
            $defaultAttr = array(
                'id'    => 'liveChat',
                'class' => '',
            );
            $attr = wp_parse_args($attr, $defaultAttr);

            if(!isset($_SESSION['kandyLiveChatUserInfo'])){
                $attr['class'] .= ' kandy_hidden';
                $func = 'LiveChatUI.changeState();';
            }else{
                $userInfo = $_SESSION['kandyLiveChatUserInfo'];
                $func = 'getKandyUsers()';
            }
            $output = '
                <div id="'.$attr['id'].'" class="liveChat '.$attr['class'].'">
                    <div class="header">
                        Kandy live chat
                        <span class="closeChat handle" title="end chat" style="display: none">x</span>
                        <span class="minimize handle" title="minimize">_</span>
                        <span id="restoreBtn"></span>
                    </div>
                    <div class="liveChatBody">
                        <div id="waiting">
                            <img id="loading" width="30px" height="30px" src="'.KANDY_PLUGIN_URL . '/img/loading.gif'.'" title="loading">
                            <p>Please wait a moment...</p>
                        </div>
                        <div id="registerForm">
                            <form id="customerInfo" method="POST" action="" >
                                <label for="customerName">Your name</label>
                                <input type="text" name="customerName" id="customerName" class="" />
                                <span data-input="customerName" style="display: none" class="error"></span>
                                <label for="customerEmail">Your email</label>
                                <input type="text" name="customerEmail" id="customerEmail" class="" />
                                <span data-input="customerEmail" style="display: none" class="error"></span>
                                <button type="submit">Start chat</button>
                            </form>
                        </div>
                        <div id="ratingForm">
                            <h3 class="formTitle">Rate for <span class="agentName">'.$userInfo['agent'].'</span> </h3>
                            <form>
                                <select id="backing2b">
                                    <option title="" value="1">1</option>
                                    <option title="" value="2">2</option>
                                    <option title="" value="3">3</option>
                                    <option title="" value="4">4</option>
                                    <option title="" value="5" selected="selected">5</option>
                                </select>
                                <div class="rateit" data-rateit-backingfld="#backing2b"></div>
                                <textarea id="rateComment" rows="3" placeholder="Say something about your supporter"></textarea>
                                <a id="btnEndSession" class="button" href="javascript:;">No, thanks</a>
                                <button id="btnSendRate" type="submit">Send</button>
                            </form>
                            <div class="message">
                                <h3>Thanks you! Good bye!</h3>
                            </div>
                        </div>
                            <div class="customerService">
                                <div class="avatar">
                                    <img src="'.KANDY_PLUGIN_URL . '/img/icon-helpdesk.png'.'">
                                </div>
                                <div class="helpdeskInfo">
                                    <span class="agentName">'.$userInfo['agent'].'</span>
                                    <p class="title">Support Agent</p>
                                </div>
                            </div>
                            <div id="messageBox" class="" style="">
                                <ul>
                                    <li class="their-message"><span class="username">'.$userInfo['agent'].'</span>: Hi '.$userInfo['username'].', what brings you here?</li>
                                </ul>
                            </div>
                            <div class="formChat" style="">
                                <form id="formChat">
                                    <input type="text" value="" name="message" id="messageToSend" placeholder="Type here and press Enter to send">
                                </form>
                            </div>
                    </div>
                </div>
                <script>
                    //agent user id
                    var agent;
                    var rateData;
                    jQuery(function(){
                        '.
                        $func
                        .'
                        jQuery(".liveChat #ratingForm .rateit").bind("rated", function(){
                            var ri = jQuery(this);
                            rateData = rateData || {};
                            rateData.rate = {id: agent.main_user_id, point: ri.rateit("value")}
                        });

                        jQuery(".liveChat #ratingForm .rateit").bind("reset", function(){
                            rateData = rateData || {};
                            if(rateData.hasOwnProperty("rate")){
                                delete rateData.rate;
                            }
                        });

                    });
                </script>';
            return $output;

		}
	}

    /**
     * Setup for shortcode.
     * @return array
     */
    static function kandySetup(){

        $current_user = wp_get_current_user();
        if (!empty($current_user->ID)) {
            $assignUser = KandyApi::getAssignUser($current_user->ID);
        } else {
            $assignUser = false;
        }
        if($assignUser){
            $userName = $assignUser->user_id;
            $password = $assignUser->password;
            $kandyApiKey = get_option('kandy_api_key', KANDY_API_KEY);
            if(get_option('kandy_jquery_reload', "0")){
                wp_enqueue_script('kandy_jquery');
            }

            wp_enqueue_script("kandy_fcs_url");

            wp_enqueue_script("kandy_js_url");
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
     * Register TinyMCE Editor Button.
     *
     * @param $buttons
     * @return mixed
     */
    function register_kandy_tinymce_button( $buttons ) {
        array_push( $buttons, "|", "kandyVideo" );
        array_push( $buttons, "|", "kandyVoice" );
        array_push( $buttons, "|", "kandyPresence" );
        array_push( $buttons, "|", "kandyChat" );
	    array_push( $buttons, "|", 'kandyLiveChat');
        array_push( $buttons, "|", "kandyCoBrowsing" );
        array_push( $buttons, "|", "kandySms" );
        return $buttons;
    }

    /**
     * Add TinyMCE Plugin.
     *
     * @param $plugin_array
     * @return mixed
     */
    function add_kandy_tinymce_plugin( $plugin_array ) {

        $plugin_array['kandyVideo'] = KANDY_PLUGIN_URL . '/js/tinymce/KandyVideo.js';
        $plugin_array['kandyVoice'] = KANDY_PLUGIN_URL . '/js/tinymce/KandyVoice.js';
        $plugin_array['kandyPresence'] = KANDY_PLUGIN_URL . '/js/tinymce/KandyPresence.js';
        $plugin_array['kandyChat'] = KANDY_PLUGIN_URL . '/js/tinymce/KandyChat.js';
        $plugin_array['kandyCoBrowsing'] = KANDY_PLUGIN_URL . '/js/tinymce/KandyCoBrowsing.js';
        $plugin_array['kandyLiveChat'] = KANDY_PLUGIN_URL . '/js/tinymce/KandyLiveChat.js';
        $plugin_array['kandySms'] = KANDY_PLUGIN_URL . '/js/tinymce/KandySms.js';        return $plugin_array;
    }

    /**
     * Register Kandy Tiny Button.
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
     * Kandy Logout.
     */
    function my_kandy_logout(){
        $current_user = wp_get_current_user();
        if($current_user) {
            setcookie( 'kandy_logout', $current_user->ID, time() + 3600);
        }
    }

    /**
     * Get option HTML for kandy user select.
     *
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
     * Add script to active kandy user select 2.
     *
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

    /**
     * register user live chat session
     * @return mixed
     */
    public function kandy_register_guest()
    {
        if(isset($_POST) && !empty($_POST)){
            $username = $_POST['customerName'];
            $userEmail = $_POST['customerEmail'];
            //Save user info to database
            $userInfo = array(
                'username'  => $username,
                'email'     => $userEmail
            );
            $errors = [];
            if( !$userEmail || !is_email($userEmail)){
                $errors['customerEmail'] = __('Please provide a valid email', 'kandy');
            }
            if(!$username){
                $errors['customerName'] = __('Please enter your name', 'kandy');
            }
            if(empty($errors)){
                if(!isset($_SESSION['kandyLiveChatUserInfo'])){
                    $_SESSION['kandyLiveChatUserInfo'] = $userInfo;
                }
                echo json_encode($userInfo);exit;
            }else{
                echo json_encode(array('errors' => $errors));exit;
            }
        }

    }

    /**
     * Get agent - user pair for chatting
     * - If kandy user lastSeen is greater than 10 secs, we consider user is not online
     * - If kandy user lastSeen is less than 3 secs, we consider user is online
     * @return mixed
     */
    public function kandy_get_free_user()
    {
        global $wpdb;
        $kandyApi = new KandyApi();
        $freeUser = null;
        $availableAgent = null;
        $fakeEndTime = PHP_INT_MAX;
        //get all unassigned users
        $kandyUserTable = $wpdb->prefix . 'kandy_users';
        $kandyLiveChatUser = get_option('kandy_live_chat_users','[]');
        $kandyLiveChatUser = json_decode($kandyLiveChatUser);
        $kandyLiveChatUser = implode('","', $kandyLiveChatUser);

        $kandyLiveChatTable = $wpdb->prefix . 'kandy_live_chat';
        $userTable = $wpdb->prefix . 'users';
        $liveChatSessionInfo = $_SESSION['kandyLiveChatUserInfo'];
        $agentType = KANDY_USER_TYPE_AGENT;
        $userQuery ="SELECT CONCAT(user_id, '@', domain_name) AS full_user_id, password
            FROM $kandyUserTable WHERE user_id IN(\"".$kandyLiveChatUser."\")
            GROUP BY full_user_id ";

        $users = $wpdb->get_results($userQuery, OBJECT_K);
        $agentQuery =
            "SELECT CONCAT(user_id,'@',domain_name) AS full_user_id,
                $kandyUserTable.password as password, $userTable.user_nicename AS username,
                $kandyUserTable.main_user_id, max(end_at) as last_chat
            FROM $kandyUserTable
            INNER JOIN $userTable
            ON $kandyUserTable.main_user_id = $userTable.id
            LEFT JOIN $kandyLiveChatTable
            ON $kandyLiveChatTable.agent_user_id = $kandyUserTable.user_id
            WHERE type = $agentType
            GROUP BY full_user_id
            ORDER by last_chat ASC";

        $agents = $wpdb->get_results($agentQuery, OBJECT_K);
        $arrayUsers = array_merge(array_keys($users), array_keys($agents));
        $lastSeen = $kandyApi->getLastSeen($arrayUsers);
        if($lastSeen){
            if($lastSeen->message == 'success'){
                //current time of kandy server
                $serverTimestamp = $lastSeen->result->server_timestamp;
                foreach($lastSeen->result->users as $user){
                    //get user
                    if(isset($users[$user->full_user_id]) && !$freeUser){
                        //get users not online in last 10 secs
                        if(($serverTimestamp - $user->last_seen) > 10000){
                            $freeUser = $user;
                            $freeUser->password = $users[$user->full_user_id]->password;
                        }
                    }
                    //get agent
                    if(!$availableAgent) {
                        // get agents online in last 3 secs
                        if(isset($agents[$user->full_user_id])){
                            $userId = current(explode('@',$user->full_user_id));
                            $lastSeen = $serverTimestamp - $user->last_seen;
                            //user considered currently online
                            if($lastSeen < 3000) {
                                if($agents[$user->full_user_id]->last_chat == $fakeEndTime) {
                                    //user currently in chat, so we find someone else
                                    continue;
                                } else {
                                    $availableAgent = $user;
                                    $availableAgent->user_id = $userId;
                                    $availableAgent->username = $agents[$user->full_user_id]->username;
                                    $availableAgent->main_user_id = $agents[$user->full_user_id]->main_user_id;
                                }

                            } elseif($lastSeen > 10000) {
                                // may be user not online for a while,
                                // may be there is something wrong, the session is still considered connected,
                                // need to update session end time for the next use
                                if($agents[$user->full_user_id]->last_chat == $fakeEndTime){
                                    $wpdb->update($kandyLiveChatTable,
                                        array('end_at' => time()),
                                        array(
                                            'agent_user_id' => $userId,
                                            'end_at'        => $fakeEndTime
                                        )
                                    );
                                }
                            }
                        }
                    }
                    if($freeUser && $availableAgent) break;
                }
            }

            if($freeUser && $availableAgent){
//                \Session::set('kandyLiveChatUserInfo.agent', $availableAgent->user_id);
                $liveChatSessionInfo['agent'] = $availableAgent->user_id;
                $now = time();
                $wpdb->insert($kandyLiveChatTable,
                    array(
                        'agent_user_id'     => $availableAgent->user_id,
                        'customer_user_id'  => $freeUser->full_user_id,
                        'customer_name'     => $liveChatSessionInfo['username'],
                        'customer_email'    => $liveChatSessionInfo['email'],
                        'begin_at'          => $now,
                        'end_at'            => PHP_INT_MAX
                    ),
                    array(
                        '%s', '%s', '%s', '%s', '%s'
                    )
                );
                //save last insert id for user later
                $liveChatSessionInfo['sessionId'] = $wpdb->insert_id;
                $_SESSION['kandyLiveChatUserInfo'] = $liveChatSessionInfo;
                $result = array(
                    'status' => 'success',
                    'user'  => $freeUser,
                    'agent' => $availableAgent,
                    'apiKey' => get_option('kandy_api_key', KANDY_API_KEY)
                );
            }else{
                /*
                 * agent not available: -3
                 * user not available -2
                 */
                $code = (!$freeUser) ? '-2' : '-3';
                $result = array(
                    'code'      => $code,
                    'status'    => 'fail'
                );
            }
        }else{
            $result = array(
                'status'    => 'fail',
                'code'      => -1 // cannot get last_seen
            );
        }
        echo json_encode($result);exit;
    }

    public function kandy_end_chat_session() {
        global $wpdb;
        if(isset($_SESSION['kandyLiveChatUserInfo']) &&
            ($chatSessionInfo = $_SESSION['kandyLiveChatUserInfo'])){
            $currentSession = intval($chatSessionInfo['sessionId']);
            //save end session time
            $wpdb->update(
                $wpdb->prefix . 'kandy_live_chat',
                array(
                    'end_at' => time(),
                ),
                array( 'id' => $currentSession )
            );
            //delete cookie
            unset($_SESSION['kandyLiveChatUserInfo']);
        }
        if((defined('DOING_AJAX') && DOING_AJAX)){
            echo json_encode(array(
                'status'    => 'success',
            ));exit;
        }
        wp_redirect($_SERVER['HTTP_REFERER']);exit;
    }

    function kandy_rate_agent() {
        global $wpdb;
        $rate = $_POST['rate'];
        $userId = $rate['id'];
        $point = $rate['point'];
        $comment = $_POST['comment'];
        if(!isset($_SESSION['kandyLiveChatUserInfo'])){
            echo json_encode(array(
                'success' => false,
                'message' => 'not allowed'
            ));exit;
        }else {
            $liveChatSessionInfo = $_SESSION['kandyLiveChatUserInfo'];
            if(!$userId){
                $result = array(
                    'success'   => false,
                    'message'   => 'agent is not specified'
                );
            }else{
                if(isset($liveChatSessionInfo['rated'])){
                    $result = array(
                        'success'   => true,
                        'message'   => 'Already rated'
                    );
                }else{
                    $now = time();

                    $wpdb->insert($wpdb->prefix . 'kandy_live_chat_rate',array(
                        'main_user_id'  => $userId,
                        'rated_time'    => $now,
                        'point'         => intval($point),
                        'rated_by'      => $liveChatSessionInfo['email'],
                        'comment'       => htmlspecialchars($comment)
                    ));
                    $liveChatSessionInfo['rated'] = true;
                    $_SESSION['kandyLiveChatUserInfo'] = $liveChatSessionInfo;
                    $result = array(
                        'success'   => true,
                    );
                }
            }
        }
        echo json_encode($result);exit;
    }

    /**
     * change a kandy user type to chat agent
     */
    function kandy_add_chat_agent() {
        global $wpdb;
        $userId = $_GET['id'];
        $affectedRow = $wpdb->update(
            $wpdb->prefix . 'kandy_users',
            array(
                'type'  => KANDY_USER_TYPE_AGENT,
            ),
            array('id'  => $userId)
        );
        if($affectedRow){
            $result = array(
                'success'   => true,
            );
        }else{
            $result = array(
                'success'   => false,
                'message'   => 'Cannot add user'
            );
        }
        echo json_encode($result);exit;
    }

}
