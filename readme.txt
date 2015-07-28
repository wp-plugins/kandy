=== Kandy ===
Contributors: kandy-io
Tags: kandy, webrtc, audio, video, chat, streaming, video-streaming, live-streaming, peer-to-peer video, collaboration
Requires at least: 3.8
Tested up to: 4.1
Stable tag: 2.2.1
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Kandy Plugin is a full-service cloud platform that enables real-time communications for business applications.

== Description ==
This WordPress plugin encapsulates Kandy’s JS SDK and Restful APIs. Kandy is a product by GENBAND (www.genband.com) that utilizes WebRTC to enable peer to peer audio and video calls and chat. SMS and PSTN calling support will be added to this package in the near future.
With this plugin, you can enable video and audio calling between two users that are logged into your WordPress website.
Think of pages where you anticipate users collaborating with each other, possibly to discuss content on those pages. Your users could start a video call with other online users and enhance the collaboration experience.

Home page: http://www.kandy.io/

<strong>FEATURES</strong>

<strong>KANDY</strong> makes use of a variety of Internet technology and for the most part you won't have to be concerned with how <strong>KANDY</strong> manages this and can focus solely on what you're trying to accomplish. However, it's helpful to understand some of the principles of how <strong>KANDY</strong> works.

* <strong>KANDY</strong>, of course, makes extensive usage of HTTP/HTTPS on ports 80/443 for much of it's communications between your clients/servers and the <strong>KANDY</strong> systems.

* Transactions with real-time media such as video and/or voice utilize the emerging standard of WebRTC. The <strong>KANDY</strong> libraries/SDKs manage all of the WebRTC transactions, so the developer can remain focused on the application. WebRTC is supported by Google's Chrome, Mozilla Firefox and Opera. Microsoft's Internet Explorer does not support WebRTC and <strong>KANDY</strong> will shortly be providing a plug-in module to provide WebRTC support within IE.

* For some transactions (like instant messaging) and state information (presence), <strong>KANDY</strong> utilizes WebSockets or Long Polling depending upon the browser specifics. The <strong>KANDY</strong> JavaScript library creates these connections depending on your application regarding and will manage these without any requirement from your application.

<strong>HOW TO USE</strong>

- Login to http://www.kandy.io/ to achieve api key, domain secret key and domain name then enter those information to Kandy plugin setting page.

- Create new page with kandy shortcode syntax:

    + <strong>Kandy Video Call Button</strong>

        [kandyVideoButton class = "myButtonStyle" id = "my-video-button"]

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

        [kandyStatus class="myStatusStyle" id="myStatus"]

        [kandyAddressBook class="myAddressBookStyle" id="myContact"]


    + Kandy Chat

        [kandyChat class="myChatStyle" id ="my-chat"]


== Frequently Asked Questions ==
1. <strong>Kandy Shortcode not working:</strong> check your kandy api key, domain secret key, user assignment for your application at Kandy > Settings.

2. <strong>Internationalizing:</strong> get the /languages/kandy.pot file and make your /languages/*.mo file to locale your language.

== Screenshots ==
1. Kandy Showcase
2. Kandy Settings
3. Kandy User Assignment
4. Kandy edit kandy user assignment
5. Kandy Customization
6. Kandy edit css file
7. Kandy Help

== Changelog ==
Version  2.2
- Implement PSTN Call
- Add AddContactLabel attribute
- Fixed label attribute in shortcode
- Fixed multiple voice call
Version 1.4
- Implement Voice Call feature
- Implement Video Call feature
- Implement Address Book feature
- Implement Chat feature

