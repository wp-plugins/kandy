<?php
// KANDY USER FILTERING STATUS
define('KANDY_USER_ALL', 1);
define('KANDY_USER_ASSIGNED', 2);
define('KANDY_USER_UNASSIGNED', 3);

class KandyApi{
    /**
     * Get Kandy User Data for assignment table.
     *
     * @param int $limit
     *   Limit User
     * @param int $offset
     *   Offset
     * @return array
     *   Array User Object
     */

    public static function getUserData($limit = 10, $offset = 0)
    {

        // Change the number of rows with the limit() call.
        $result = get_users(array("number" => $limit, "offset" => $offset));

        $rows = array();
        foreach ($result as $row) {
            $url = self::page_url(
                array(
                    "action" => "edit",
                    "id" => $row->ID
                )
            );

            $kandyUser = self::getAssignUser($row->ID);

            $tableCell = array(
                'ID'  => $row->ID,
                'username' => $row->display_name,
                "kandy_user_id"=> ($kandyUser) ? $kandyUser->user_id : null,
                "action" => "<a href='". $url."' class='button kandy_edit'>". __("Edit", 'kandy'). "</a>"
            );
            $rows[] = $tableCell;
        }
        return $rows;
    }


    /**
     * Get domain access token
     * @return array A list of message and data
     * @throws RestClientException
     */
    public static function getDomainAccessToken()
    {
        require_once dirname(__FILE__) . '/RestClient.php';

        $kandyApiKey = get_option('kandy_api_key', KANDY_API_KEY);
        $kandyDomainSecretKey = get_option(
            'kandy_domain_secret_key',
            KANDY_DOMAIN_SECRET_KEY
        );
        $params = array(
            'key'               => $kandyApiKey,
            'domain_api_secret' => $kandyDomainSecretKey
        );

        $fieldsString = http_build_query($params);
        $url = KANDY_API_BASE_URL . 'domains/accesstokens' . '?'
            . $fieldsString;

        try {
            $restClientObject = new RestClient();
            $response = $restClientObject->get($url)->getContent();
        } catch (Exception $ex) {

            return array(
                'success' => false,
            );
        }

        $response = json_decode($response);
        if ($response->message == 'success') {
            return array(
                'success' => true,
                'data'    => $response->result->domain_access_token,
            );
        } else {
            return array(
                'success' => false,
                'message' => $response->message
            );
        }
    }

    /**
     * List Kandy User from database
     * @param $type
     * @param bool $remote
     * @return array
     */
    public static function listUsers($type = KANDY_USER_ALL, $remote = false)
    {
        $result = array();
        require_once dirname(__FILE__) . '/RestClient.php';
        // get data from server
        if ($remote) {
            $getTokenResponse = self::getDomainAccessToken();
            if ($getTokenResponse['success']) {
                $domainAccessToken = $getTokenResponse['data'];
                $params = array(
                    'key' => $domainAccessToken
                );

                $fieldsString = http_build_query($params);
                $url = KANDY_API_BASE_URL . 'domains/users' . '?'
                    . $fieldsString;
                $headers = array(
                    'Content-Type: application/json'
                );

                try {
                    $restClientObject = new RestClient();
                    $response = $restClientObject->get($url, $headers)->getContent();
                } catch (Exception $ex) {

                    return array(
                        'success' => false,
                        'message' => $ex->getMessage()
                    );
                }
                $response = json_decode($response);

                if ($response) {
                    $data = $response->result;
                    $result = $data->users;
                }
            }
        } else {
            global $wpdb;
            $getDomainNameResponse = self::getDomain();
            if($getDomainNameResponse['success']){
                $domainName = $getDomainNameResponse['data'];
                if ($type == KANDY_USER_ALL) {


                    $result = $wpdb->get_results(
                        "SELECT *
                             FROM {$wpdb->prefix}kandy_users
                             WHERE domain_name = '". $domainName ."'");
                } else {
                    if ($type == KANDY_USER_ASSIGNED) {

                        $result = $wpdb->get_results(
                            "SELECT *
                             FROM {$wpdb->prefix}kandy_users
                             WHERE main_user_id IS NOT NULL
                             AND domain_name = '". $domainName ."'");

                    } else {
                        if ($type == KANDY_USER_UNASSIGNED) {
                            $result = $wpdb->get_results(
                                "SELECT *
                             FROM {$wpdb->prefix}kandy_users
                             WHERE main_user_id = ''
                             AND domain_name = '". $domainName ."'");
                        }
                    }
                }
            }

        }

        return $result;
    }

    /**
     * get Assigned Kandy User By main_user_id
     * @param $mainUserId
     * @return mixed
     */
    public static function getAssignUser($mainUserId)
    {
        global $wpdb;
        $result = null;
        $getDomainNameResponse = self::getDomain();

        if ($getDomainNameResponse['success']) {
            $domainName = $getDomainNameResponse['data'];
            $result = $wpdb->get_results(
                            "SELECT *
                             FROM {$wpdb->prefix}kandy_users
                             WHERE main_user_id = ". $mainUserId ."
                             AND domain_name = '". $domainName ."'");
        }
        if(!empty($result)){
            return $result[0];
        }else {
            $result = null;
        }

        return $result;
    }

    /**
     * get kandy user by user_id
     * @param $kandyUserId
     * @return mixed
     */
    public static function getUserByUserId($kandyUserId){
        global $wpdb;
        $result = null;
        $getDomainNameResponse = self::getDomain();

        if ($getDomainNameResponse['success']) {
            $domainName = $getDomainNameResponse['data'];

            $result = $wpdb->get_results(
                "SELECT *
                             FROM {$wpdb->prefix}kandy_users
                             WHERE user_id = '". $kandyUserId ."'
                             AND domain_name = '". $domainName ."'");

        }
        if(!empty($result)){
            return $result[0];
        } else {
            $result = null;
        }
        return $result;
    }

    /**
     * get kandy user by email
     * @param $kandyUserId
     * @return mixed
     */
    public static function getUserByKandyUserMail($kandyUserMail){
        global $wpdb;
        $result = null;
        $getDomainNameResponse = self::getDomain();

        if ($getDomainNameResponse['success']) {
            $domainName = $getDomainNameResponse['data'];

            $result = $wpdb->get_results(
                "SELECT main_user_id
                             FROM {$wpdb->prefix}kandy_users
                             WHERE email = '". $kandyUserMail ."'
                             AND domain_name = '". $domainName ."'");

        }
        if(!empty($result)){
            $mainUserId = $result[0]->main_user_id;

            $result = get_user_by('id', $mainUserId);
        } else {
            $result = null;
        }
        return $result;
    }

    /**
     * Get the domain from domain key in the configuration.
     *
     * @param bool $sync
     *   Force to sync(true).
     *
     * @return array
     *   Domain name result.
     */
    public static function getDomain($sync = false)
    {
        $myDomainName = get_option("kandy_domain_name");

        //no sync use database value
        if(!empty($myDomainName) && !$sync){
            return array(
                'success' => true,
                'data'    => $myDomainName,
            );
        }
        require_once dirname(__FILE__) . '/RestClient.php';

        $kandyApiKey = get_option('kandy_api_key', KANDY_API_KEY);
        $getTokenResponse = self::getDomainAccessToken();
        if ($getTokenResponse['success']) {
            $domainAccessToken = $getTokenResponse['data'];
            $params = array(
                'key'               => $domainAccessToken,
                'domain_api_key' => $kandyApiKey
            );

            $fieldsString = http_build_query($params);
            $url = KANDY_API_BASE_URL . 'accounts/domains/details' . '?'
                . $fieldsString;

            try {
                $restClientObject = new  RestClient();
                $response = $restClientObject->get($url)->getContent();
            } catch (Exception $ex) {

                return array(
                    'success' => false,
                    'message' => $ex->getMessage()
                );
            }

            $response = json_decode($response);
            if ($response->message == 'success') {
                update_option("kandy_domain_name",  $response->result->domain->domain_name);
                return array(
                    'success' => true,
                    'data'    => $response->result->domain->domain_name,
                );
            } else {
                return array(
                    'success' => false,
                    'message' => $response->message
                );
            }
        } else {
            return array(
                'success' => false,
                'message' => 'Invalid Domain Request'
            );
        }



    }
    /**
     * Get all users from Kandy and import/update to kandy_user
     *
     * @return array A json status and message
     */
    public static function syncUsers()
    {
        global $wpdb;
        $kandyUsers = self::listUsers(KANDY_USER_ALL, true);
        $getDomainNameResponse = self::getDomain();

        if ($getDomainNameResponse['success']) {
            $domainName = $getDomainNameResponse['data'];

            // The transaction opens here.
            $wpdb->query('START TRANSACTION');
            $receivedUsers = array();
            try {
                foreach($kandyUsers as $kandyUser){
                    $receivedUsers[] = $kandyUser->user_id;
                    $format = array(
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s',
                        '%s'
                    );

                    $dataValues = array(
                        'user_id' => $kandyUser->user_id,
                        'first_name' => $kandyUser->user_first_name,
                        'last_name' => $kandyUser->user_last_name,
                        'password' => $kandyUser->user_password,
                        'email' => $kandyUser->user_email,
                        'domain_name' => $kandyUser->domain_name,
                        'api_key' => $kandyUser->user_api_key,
                        'api_secret' => $kandyUser->user_api_secret,
                        'updated_at' => date("Y-m-d H:i:s"),
                    );
                    $kandyUserModel = self::getUserByUserId($kandyUser->user_id);

                    if(!$kandyUserModel){
                        // insert
                        $format[] = '%s';
                        $dataValues['created_at'] = date("Y-m-d H:i:s");
                        $wpdb->insert($wpdb->prefix. "kandy_users", $dataValues, $format);

                    } else {
                        //update
                        $wpdb->update(
                            $wpdb->prefix . "kandy_users",
                            $dataValues,
                            array('user_id' => $kandyUser->user_id,
                                'domain_name' => $domainName
                            ),
                            $format,
                            array("%s", "%s")
                        );

                    }
                }//end foreach
                if(!empty($receivedUsers)){
                    $inArrayStr = "";
                    foreach($receivedUsers as $receivedUser){
                        $inArrayStr .= "'" . $receivedUser ."',";
                    }
                    $inArrayStr = trim($inArrayStr, ",");
                    $wpdb->query( "DELETE FROM {$wpdb->prefix}kandy_users
                                   WHERE domain_name = '" . $domainName . "'
                                   AND user_id NOT IN (" . $inArrayStr . ")" );
                }

                $wpdb->query('COMMIT');
                $result = array(
                    'success' => true,
                    'message' => "Sync successfully"
                );
            }
            catch (Exception $ex) {
                $wpdb->query('ROLLBACK');

                $result = array(
                    'success' => false,
                    'message' => "Error Data"
                );
            }

        } else {
            $result = array(
                'success' => false,
                'message' => "Cannot get domain name."
            );
        }
        return $result;
    }

    /**
     * Assign Kandy User
     * @param $kandyUserId
     * @param $mainUserId
     * @return bool
     */
    public static function assignUser($kandyUserId, $mainUserId){
        global $wpdb;
        try{
            $getDomainNameResponse = self::getDomain();
            if ($getDomainNameResponse['success'] == true) {
                $domainName = $getDomainNameResponse['data'];

                $wpdb->update(
                    $wpdb->prefix . 'kandy_users',
                    array('main_user_id' => NULL),
                    array(
                        'main_user_id' => $mainUserId,
                        'domain_name'  => $domainName
                    )
                );

                $wpdb->update(
                    $wpdb->prefix . 'kandy_users',
                    array('main_user_id' => $mainUserId),
                    array(
                        'user_id' => $kandyUserId,
                        'domain_name'  => $domainName
                    ),
                    array('%d'),
                    array('%s', '%s')
                );
                return true;
            } else {
                return false;
            }

        } catch(Exception $ex){

            return false;
        }

    }

    /**
     * Unassign kandy user
     * @param $mainUserId
     * @return bool
     */
    public static function unassignUser($mainUserId){
        global $wpdb;
        try{
            $getDomainNameResponse = self::getDomain();
            if ($getDomainNameResponse['success'] == true) {
                $domainName = $getDomainNameResponse['data'];

                $wpdb->update(
                    $wpdb->prefix . 'kandy_users',
                    array('main_user_id' => NULL),
                    array(
                        'main_user_id' => $mainUserId,
                        'domain_name'  => $domainName
                    )
                );

                return true;
            } else {
                return false;
            }
        } catch(Exception $ex){

            return false;
        }

    }

    /**
     * Get Page Url
     * @param null $additional_params
     * @return mixed
     */
    public static  function page_url($additional_params = NULL) {
        $params = array();
        if(!isset($additional_params['page']) && isset($_GET["page"])){
            $params["page"] = $_GET["page"];
        }
        if ( is_array($additional_params) ) {
            $params = array_merge($params, $additional_params);
        }
        return admin_url('admin.php?' . http_build_query($params));
    }

    /**
     * Redirect with a message
     * @param $url
     * @param $message
     * @param string $type
     */
    public static function redirect($url, $message, $type ="updated"){

        echo "<div class ='". $type. "'><p>" . $message. "</p></div>";
        echo "<meta http-equiv='refresh' content='0;url=$url' />";
    }

    /**
     * Kandy Logout
     * @param $userId
     * @return array
     */
    public static function kandyLogout($userId){
        $result = array();
        $assignUser = KandyApi::getAssignUser($userId);

        if($assignUser){
            $userName = $assignUser->user_id;
            $password = $assignUser->password;
            $kandyApiKey = get_option('kandy_api_key', KANDY_API_KEY);
            wp_enqueue_script("kandy_js_url");
            wp_enqueue_script("kandy_fcs_url");
            $output = "";
            $output .="<script type='text/javascript' src='". KANDY_JQUERY ."'></script>";
            $output .="<script type='text/javascript' src='". KANDY_JS_URL ."'></script>";
            $output .="<script type='text/javascript' src='". KANDY_FCS_URL ."'></script>";
            $output .="<script>if (window.login == undefined){window.login = function() {
                        KandyAPI.Phone.login('" . $kandyApiKey . "', '" . $userName . "', '" . $password . "');
                    };
                    window.kandy_logout = function() {
                                        KandyAPI.Phone.logout();
                    };
                }</script>";
            $output .="<script type='text/javascript' src='". KANDY_PLUGIN_URL . "/js/kandyWordpress.js" ."'></script>";
            setcookie( 'kandy_logout', '1', time() - 3600);
            echo $output;
        } else {
            $result = array("success" => false, "message" => 'Can not found kandy user', 'output' => '');
        }

        return $result;
    }
}