=== Kandy ===
Contributors: Kodeplusdev
Tags: Kandy
Requires at least: 3.8
Tested up to: 4.1
Stable tag: 1.4
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Kandy Plugin is a full-service cloud platform that enables real-time communications for business applications.

== Description ==
Kandy Plugin is a full-service cloud platform that enables real-time communications for business applications.

Home page: http://www.kandy.io/

<strong>FEATURES</strong>

<strong>KANDY</strong> makes use of a variety of Internet technology and for the most part you won't have to be concerned with how <strong>KANDY</strong> manages this and can focus solely on what you're trying to accomplish. However, it's helpful to understand some of the principles of how <strong>KANDY</strong> works.

* <strong>KANDY</strong>, of course, makes extensive usage of HTTP/HTTPS on ports 80/443 for much of it's communications between your clients/servers and the <strong>KANDY</strong> systems.

* Transactions with real-time media such as video and/or voice utilize the emerging standard of WebRTC. The <strong>KANDY</strong> libraries/SDKs manage all of the WebRTC transactions, so the developer can remain focused on the application. WebRTC is supported by Google's Chrome, Mozilla Firefox and Opera. Microsoft's Internet Explorer does not support WebRTC and <strong>KANDY</strong> will shortly be providing a plug-in module to provide WebRTC support within IE.

* For some transactions (like instant messaging) and state information (presence), <strong>KANDY</strong> utilizes WebSockets or Long Polling depending upon the browser specifics. The <strong>KANDY</strong> JavaScript library creates these connections depending on your application regarding and will manage these without any requirement from your application.

<strong>HOW TO USE</strong>

- Create new page with kandy shortcode syntax:

    + <strong>Kandy Video Call Button</strong>

        [kandyVideoButton class = "myButtonStype" id = "my-video-button"]

    + <strong>Kandy Video</strong>

        [kandyVideo title = "Me" id = "myVideo"     style = "width: 300px; height: 225px;"]

    + <strong>Kandy Voice Call Button</strong>

        [kandyVoiceButton class = "myButtonStyle" id = "my-voice-button"]

    + <strong>Kandy Status</trong>

        [kandyStatus class = "myStatusStyle" id = "myStatus"]

    + <strong>Kandy Address Book</strong>

        [kandyAddressBook class = "myAddressBookStyle" id = "myContact"]


    + <strong>Kandy Chat</strong>

        [kandyChat class = "myChatStyle" id = "my-chat" contactLabel = "Contacts"]




- <strong>Quick Example</strong>:

    + Kandy Voice Call

        [kandyVoiceButton class= "myButtonStyle" id ="my-voice-button"]


    + Kandy Video Call(Video should be inline)

       [kandyVideoButton class="myButtonStyle"]

       [kandyVideo title="Me" id="myVideo"]

       [kandyVideo title="Their"  id="theirVideo"]

    + Kandy Presence

        [kandyStatus class="myStatusStype" id="myStatus"]

        [kandyAddressBook class="myAddressBookStyle" id="myContact"]


    + Kandy Chat

        [kandyChat class="myChatStyle" id ="my-chat"]



<strong>More information:</strong> https://github.com/kodeplusdev/kandywordpress

== Frequently Asked Questions ==
1. <strong>Kandy Shortcode not working:</strong> check your kandy api key, domain secret key for your application at Kandy > Settings.

2. <strong>Internationalizing:</strong> get the /languages/kandy.pot file and make your /languages/*.mo file to locale your language.

== Screenshots ==
1. Kandy Showcase
2. Kandy Settings
3. Kandy User Assignment
4. Kandy edit kandy user assignment
5. Kandy Cutomization
6. Kandy edit css file
7. Kandy Help


