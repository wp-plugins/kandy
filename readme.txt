=== Kandy ===
Contributors: kodeplusdev
Tags: Kandy
Requires at least: 1.0
Tested up to: 1.4.1
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Kandy Plugin is a full-service cloud platform that enables real-time communications for business applications.

== Description ==
Kandy Plugin is a full-service cloud platform that enables real-time communications for business applications.

Home page: http://www.kandy.io/

<strong>Key Features</strong>

KANDY makes use of a variety of Internet technology and for the most part you won't have to be concerned with HOW KANDY manages this and can focus solely on WHAT you're trying to accomplish. However, it's helpful to understand some of the principles of how KANDY works.

* KANDY, of course, makes extensive usage of HTTP/HTTPS on ports 80/443 for much of it's communications between your clients/servers and the KANDY systems.
* Transactions with real-time media such as video and/or voice utilize the emerging standard of WebRTC. The KANDY libraries/SDKs manage all of the WebRTC transactions, so the developer can remain focused on the application. WebRTC is supported by Google's Chrome, Mozilla Firefox and Opera. Microsoft's Internet Explorer does not support WebRTC and KANDY will shortly be providing a plug-in module to provide WebRTC support within IE.
* For some transactions (like instant messaging) and state information (presence), KANDY utilizes WebSockets or Long Polling depending upon the browser specifics. The KANDY JavaScript library creates these connections depending on your application regarding and will manage these without any requirement from your application.

<strong>Documentation</strong>

- Create new page with kandy shortcode syntax:

    + Kandy Video Call Button
        [kandyVideoButton
            class = "myButtonStype"
            id = "my-video-button"
            incomingLabel = 'Incoming Call...'
            incomingButtonText = 'Answer'
            callOutLabel = 'User to call'
            callOutButtonText = 'Call'
            callingLabel = 'Calling...'
            callingButtonText = 'End Call'
            onCallLabel = 'You are connected!'
            onCallButtonText = 'End Call']
        [/kandyVideoButton]

    + Kandy Voice Call Button
        [kandyVoiceButton
            class = "myButtonStype"
            id = "my-voice-button"
            incomingLabel = 'Incoming Call...'
            incomingButtonText = 'Answer'
            callOutLabel = 'User to call'
            callOutButtonText = 'Call'
            callingLabel = 'Calling...'
            callingButtonText = 'End Call'
            onCallLabel = 'You are connected!'
            onCallButtonText = 'End Call']
        [/kandyVoiceButton]

    + Kandy Video
        [kandyVideo
            title = "Me"
            id = "myVideo"
            style = "width: 300px; height: 225px;background-color: darkslategray;"]
        [/kandyVideo]

    + Kandy Status
        [kandyStatus
            class = "myStatusStyle"
            id = "myStatus"
            title = "My Status"
            style = "..."]
        [/kandyStatus]

    + Kandy Address Book
        [kandyAddressBook
            class = "myAddressBookStyle"
            id = "myContact"
            title = "My Contact"
            userLabel = "User"
            searchLabel = "Search"
            searchResultLabel = "Directory Search Results"
            style = "..."
            ]
        [/kandyAddressBook]

    + Kandy Chat
        [kandyChat
            class = "myChatStyle"
            id = "my-chat"
            contactLabel = "Contacts"]
        [/kandyChat]



    - Example:

    + Kandy Voice Call

        [kandyVoiceButton class= "myButtonStyle" id ="my-voice-button"][/kandyVoiceButton]


    + Kandy Video Call

       [kandyVideoButton class="myButtonStype"][/kandyVideoButton]
       [kandyVideo title="Me" id="myVideo" style = "width: 300px; height: 225px;background-color: darkslategray;"] [/kandyVideo]
       [kandyVideo title="Their"  id="theirVideo" style = "width: 300px; height: 225px;background-color: darkslategray;"][/kandyVideo]

    + Kandy Presence

        [kandyStatus class="myStatusStype" id="myStatus"][/kandyStatus]
        [kandyAddressBook class="myAddressBookStyle" id="myContact"][/kandyAddressBook]


    + Kandy Chat

        [kandyChat class="myChatStyle" id ="my-chat"][/kandyChat]

==========================================================================================

KANDY ADMINISTRATION
+Settings


+ User assignment:

Click KANDY USER ASSIGNMENT to sync kandy user for your application
Select user and click edit button to assign kandy user

+ Style customization
Click KANDY STYLE CUSTOMIZATION to edit kandy shortcode(video, voice, chat...) style
Select appropriate file then click edit

+ Script customization

Click KANDY SCRIPT CUSTOMIZATION to edit kandy shortcode(video, voice, chat...) script
Select appropriate file then click edit
All support callback:
window.loginsuccess_callback = function () {
   //do something when you login successfully
}
window.loginfailed_callback = function () {
    //do something when you login fail
}
window.callincoming_callback = function (call, isAnonymous) {
    //do something when your are calling
}
window.oncall_callback = function (call) {
    //do something when you are oncall
}
window.callanswered_callback = function (call, isAnonymous) {
    //do something when someone answer your call
}

window.callended_callback = function () {
   //do something when someone end  your call
}

window.answerVoiceCall_callback = function (stage) {


    //do something when you answer voice call


}

window.answerVideoCall_callback = function (stage) {
    //do something when you answer video call
}
window.makeCall_callback = function (stage) {
   //do something when you make call
}


window.endCall_callback = function (stage) {
   //do something when you click end call button
}

window.remotevideoinitialized_callack(videoTag){

   //do something with your remote video

}

window.localvideoinitialized_callback = function(videoTag){
    //do some thing with your local video
}

window.presencenotification_callack = function() {
    //do something with status notification

}
================================================================================
KANDY API
You can use kandy plugin anywhere in your code by following code:

Load Kandy Plugin

require_once(KANDY_PLUGIN_DIR . "/api/kandy-api-class.php");
After load kandy plugin succucessfully you can use all support api:

1. Get kandy user data for assignment table

KandyApi::getUserData();
Return: kandy user object array

2. Get kandy domain access token

KandyApi::getDomainAccessToken();
Return: array

    $result = array("success" => true,
                "data" => "data",
                "message" => '')
OR
    $result = array("success" => false,
                "data" => "data",
                "message" => "message")
3. Get the kandy domain

KandyApi::getDomain();
Get kandy domain from kandy settings or remote server

Return: array

    $result = array("success" => true,
                "data" => "data",
                "message" => '');
OR
    $result = array("success" => false,
                "data" => "data",
                "message" => "message");
4. List Kandy User from database/remote

    KandyApi::listUsers($type = KANDY_USER_ALL, $remote = false);

Parameters:

    $type(int) :
        KANDY_USER_ALL: all kandy users from database/remote
        KANDY_USER_ASSIGNED: all assigned kandy users from database/remote
        KANDY_USER_UNASSIGNED: all unassigned kandy users from database/remote
    $remote(boolean) :
        If $remote = true, get kandy users from remote server(kandy server) instead of from database(local). Default is false.
Return: Kandy user object array

5. Get assigned kandy user by current user id(main_user_id)

    KandyApi::getAssignUser($mainUserId);

Parameters:
    $mainUserId(int): normal user id(1, 2, 3....)
Return kandy user object or null

6 Get kandy user by kandy user id(kandyUserId)

    KandyApi::getUserByUserId($kandyUserId)

Parameters:
    $kandyUserId(int): kandy user id without domain(user1, user2....)

Return kandy user object or null

7. Assign a normal user to kandy user

KandyApi::assignUser($kandyUserId, $mainUserId)

Parameters:
    $kandyUserId(string) : kandy user id without domain(user1, user2....)
    $mainuserId(int): normal user id(1, 2, 3....)
Return: true/false

8. Unassign a kandy user

KandyApi::unassignUser($mainUserId)
Parameters:

$mainuserId(int): normal user id(1, 2, 3....)
Return: true/false

9. Kandy User synchronization

Synchronize kandy user from remote server to local

KandyApi::syncUsers()
Return: array

$result = array(
                'success' => true,
                'message' => "Sync successfully"
            );
OR
$result = array(
                'success' => false,
                'message' => "Error Data"
            );


<strong>Troubleshooting</strong>
== Installation ==




