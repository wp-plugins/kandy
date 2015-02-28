/**
 * @author Russell Holmes
 * KandyAPI
 * @singleton
 */
var responseCodes = {
    OK: 0,
    internalServerError: 1,
    tokenExpired: 10,
    permissionDenied: 11,
    usageQuotaExceeded: 12,
    insufficientFunds: 13,
    validationFailed: 14,
    missingParameter: 15,
    invalidParameterValue: 16,
    badParameterValue: 17,
    unknownRequest: 18,
    noData: 19,
    alreadyExists: 50,
    invalidIdentifier: 51,
    invalidPassword: 52,
    doesNotExist: 53,
    invalidCountryCode: 54,
    invalidCredentials: 55,
    ajaxError: 5000
};

KandyAPI = (function () {
    return {
        /**
         * Available notification types
         * @type Object
         */
        NOTIFICATION_TYPES: {

            LONGPOLLING: "longpolling",

            SNMP: "snmp",

            WEBSOCKET: "websocket"
        }
    };
}());

/**
 * @author Russell Holmes
 * KandyAPI.Phone
 * @singleton
 * The Kandy Phone is used to make calls (audio and video), get presence notifications, and send IMs.
 */

KandyAPI.Phone = (function () {
    var me = {};

    /**
     * @property {Object} _config Configuration for KandyAPI.Phone.
     * @private
     */
    var _config = {
        listeners: {},
        kandyApiUrl: 'https://api.kandy.io/v1.1',
        mediatorUrl: 'http://service.kandy.io:8080/kandywrapper-1.0-SNAPSHOT',
        fcsConfig: {
            notificationType: KandyAPI.NOTIFICATION_TYPES.WEBSOCKET,
            restUrl: 'multispidr.kandy.io',
            restPort: '443',
            websocketIP: 'multispidr.kandy.io',
            websocketPort: '8582',
            websocketProtocol: 'wss',
            disableNotifications: null,
            protocol: 'https',
            cors: true
        },
        spidrEnv: {
            iceserver: "",
            webrtcdtls: null,
            videoContainer: "",
            pluginMode: "auto",
            pluginLogLevel: 2
        },
        messageProvider: 'fring',
        pstnOutNumber: '71',
        allowAutoLogin: false,
        ringInAudioSrcs: [
            {src: 'https://kandy-portal.s3.amazonaws.com/public/sounds/ringin.mp3', type: 'audio/mp3'},
            {src: 'https://kandy-portal.s3.amazonaws.com/public/sounds/ringin.ogg', type: 'audio/ogg'}
        ],
        ringOutAudioSrcs: [
            {src: 'https://kandy-portal.s3.amazonaws.com/public/sounds/ringout.mp3', type: 'audio/mp3'},
            {src: 'https://kandy-portal.s3.amazonaws.com/public/sounds/ringout.ogg', type: 'audio/ogg'}
        ],
        msgInAudioSrcs: [
            {src: 'https://kandy-portal.s3.amazonaws.com/public/sounds/msgin.mp3', type: 'audio/mp3'},
            {src: 'https://kandy-portal.s3.amazonaws.com/public/sounds/msgin.ogg', type: 'audio/ogg'}
        ]
    };

    /**
     * @property {String} _username Username of the currently logged in user.
     * @private
     */
    var _username = null;

    /**
     * @property {String} _userAccessToken User access token.
     * @private
     */
    var _userAccessToken = null;

    /**
     * @property {String} _domainApiKey Domain API Key token.
     * @private
     */
    var _domainApiKey = null;

    /**
     * @property {String} _userDeviceId User device ID.
     * @private
     */
    var _userDeviceId = null;

    /**
     * @property {Object} _callTypes Holds call types.
     * @private
     */
    var _callTypes = {
        INCOMING_CALL: 1,
        OUTGOING_CALL: 2
    };

    /**
     * @property {Object} _presenceTypes Types of presence.
     * @private
     */
    var _presenceTypes = {
        0: 'Available',
        1: 'Unavailable',
        2: 'Away',
        3: 'Out To Lunch',
        4: 'Busy',
        5: 'On Vacation',
        6: 'Be Right Back',
        7: 'On The Phone',
        8: 'Active',
        9: 'Inactive',
        10: 'Pending',
        11: 'Offline'
    };

    /**
     * @property {Object} _audio Holds audio nodes.
     * @private
     */
    var _audio = {};

    /**
     * @property {_callType} _callType Type of call.
     * @private
     */
    var _callType = null;

    /**
     * @property {Boolean} _isMuted If the call is muted.
     * @private
     */
    var _isMuted = false;

    /**
     * @property {boolean} _held If the call is on hold.
     * @private
     */
    var _held = false;

    /**
     * @property {Boolean} _videoStatus If the call has video.
     * @private
     */
    var _videoStatus = false;

    /**
     * @property {Boolean} _isAnonymous If the call was Anonymous.
     * @private
     */
    var _isAnonymous = false;

    /**
     * @property {String} _intraframe TODO - Fill out.
     * @private
     */
    var _intraframe = null;

    /**
     * @property {Boolean} _isIncoming True if call is incoming.
     * @private
     */
    var _isIncoming = null;

    /**
     * @property {Boolean} _isOutgoing True if call is outgoing.
     * @private
     */
    var _isOutgoing = null;

    /**
     * @property {String} _callId Current calls ID.
     * @private
     */
    var _callId = null;

    /**
     * @property {CallObject} _currentCall Current call object.
     * @private
     */
    var _currentCall = null;

    var _mediaInitiated = false;

    var _callStates = null;

    var _logger = null;

    me.events = {};

    /**
     * @event callinitiated
     * Fired when an outgoing call is initiated
     * @param {Call} fcs.call.OutgoingCall object
     * @param {String} number ID of the callee
     */
    me.events['callinitiated'] = null;

    /**
     * @event callinitiatefailure
     * Fired when an attempt to initiate an outgoing call fails
     */
    me.events['callinitiatefailure'] = null;

    /**
     * @event callincoming
     * Fired when a call is coming in
     * @param {Call} call The call object
     */
    me.events['callincoming'] = null;

    /**
     * @event callended
     * Fired when a call has ended
     */
    me.events['callended'] = null;

    /**
     * @event callendfailure
     * Fired when a call fails to end
     */
    me.events['callendfailure'] = null;

    /**
     * @event callanswered
     * Fired when a call is answered
     * @param {Call} call The call object
     * @param {Boolean} isAnonymous True if the all is anonymous
     */
    me.events['callanswered'] = null;

    /**
     * @event oncall
     * Fired while on call
     * @param {Call} call The call object
     */
    me.events['oncall'] = null;

    /**
     * @event callanswerfailure
     * Fired when a failure occurs when answering a call
     */
    me.events['callanswerfailure'] = null;

    /**
     * @event localvideoinitialized
     * @param {String} videoTag Html video tag
     * Fired when local video is initialized
     */
    me.events['localvideoinitialized'] = null;

    /**
     * @event remotevideoinitialized
     * Fired when remote video is initialized
     * @param {String} videoTag Html video tag
     */
    me.events['remotevideoinitialized'] = null;

    /**
     * @event receivingvideoset
     * Fired when the flag is set that indicates whether other party is sending video
     * @param {Boolean} isReceivingVideo True if other party is sending video, false if just audio
     */
    me.events['receivingvideoset'] = null;

    /**
     * @event presencenotification
     * @param {String} username Username of presence event
     * @param {String} state Presence state
     * @param {String} description Presence description
     * @param {String} activity Presence activity
     * Fired when presence notification is received
     */
    me.events['presencenotification'] = null;

    /**
     * @event contactsloaded
     * Fired when contacts are loaded
     */
    me.events['contactsloaded'] = null;

    /**
     * @event loginsuccess
     * Fired when logged on
     */
    me.events['loginsuccess'] = null;

    /**
     * @event loginfailed
     * Fired when a login attempt fails
     */
    me.events['loginfailed'] = null;

    /**
     * @event callrejected
     * Fired when a call is rejected
     */
    me.events['callrejected'] = null;

    /**
     * @event messagereceived
     * @param {String} username Username of im event
     * @param {String} message Im message
     * Fired when a message is received
     */
    me.events['messagereceived'] = null;

    /**
     * @method _fireEvent
     * Fires passed event
     * @private
     */
    function _fireEvent() {
        var eventName = Array.prototype.shift.apply(arguments);

        if (me.events[eventName])
            me.events[eventName].apply(me, arguments);
    }

    /**
     * @method _startIntraFrame
     * Starts infra-frame coding for compression
     * @private
     */
    function _startIntraFrame() {
        _intraframe = setInterval(function () {
            if (_currentCall) {
                _currentCall.sendIntraFrame();
            } else {
                _stopIntraFrame();
            }
        }, 5000);
    }

    /**
     * @method _stopIntraFrame
     * Stops infra-frame coding for compression
     * @private
     */
    function _stopIntraFrame() {
        if (_intraframe) {
            clearInterval(_intraframe);
        }
    }

    /*
     * @method _setLocalStream
     * Sets the local video stream
     */
    function _setLocalStream(localstreamURL) {
        var content;
        if (localstreamURL) {
            // muted="true" is required because if the stream is on it causes echo
            content = '<video id="kandyLocalVideo" width="100%" height="100%" autoplay="autoplay" muted="true" src="' + localstreamURL + '" />';

            _fireEvent('localvideoinitialized', content);
        }
    }

    /*
     * @method _toggleNativeRemoteVideo
     * Toggles the remote remote video native property
     */
    function _toggleNativeRemoteVideo(call, streamURL) {
        if (streamURL) {
            call.remoteVideoTag = '<video id="kandyRemoteVideo" width="100%" height="100%" autoplay="autoplay" src="' + streamURL + '" />';

            _fireEvent('remotevideoinitialized', call.remoteVideoTag);
        }
    }

    /**
     * @method _handleCallNotification
     * Handles incoming call notifications
     * @param {Call} call The call object
     * @private
     */
    function _handleCallNotification(call) {
        _callType = _callTypes.INCOMING_CALL;
        _currentCall = call;

        _audio.ringIn.play();

        // check if this is an anonymous call
        if (_currentCall.callerNumber.indexOf("concierge") != -1)
            _isAnonymous = true;

        if (_currentCall.canSendVideo)
            _videoStatus = true;

        _fireEvent('callincoming', _currentCall, _isAnonymous);
    }

    /**
     * @method _handleIncomingCallStateChange
     * Handles incoming call state changes
     * @param {Call} call The call object
     * @param {State} state The state of the call
     * @private
     */
    function _handleIncomingCallStateChange(call, state) {
        var callId = call.getId(),
            hold_state;

        _isIncoming = true;
        _isOutgoing = false;
        _currentCall = call;
        _callId = callId;

        hold_state = call.getHoldState();
        if (hold_state === "REMOTE_HOLD") {
            _logger.info('CALL HELD REMOTELY');
        }

        if (state === _callStates.IN_CALL) {
            if (hold_state === "LOCAL_HOLD") {
                _logger.info('ON HOLD');
            } else {
                _logger.info('ON CALL');
            }

            if (_currentCall.canSendVideo()) {
                _startIntraFrame();
            }
            _audio.ringIn.pause();
        } else if (state === _callStates.RENEGOTIATION) {

        } else if (state === _callStates.ON_HOLD) {
            _logger.info("CALL HELD REMOTELY");
        } else if (state === _callStates.RINGING) {
            _logger.info("RINGING");
            _audio.ringIn.play();
        } else if (state === _callStates.ENDED) {
            if (call) {
                _stopIntraFrame();
                if (call.statusCode === 0 || call.statusCode === undefined) {
                    _logger.info("CALL END");
                } else {
                    if ((call.statusCode >= 100 && call.statusCode <= 300)) {
                        _logger.error("WebRTC ERROR");
                    } else {
                        _logger.error("ERROR");
                    }
                }
                _currentCall = null;
                _initialize();
                _fireEvent('callended', call);
            }
        } else if (state === _callStates.REJECTED) {
            _logger.info("REJECTED");
        } else if (state === _callStates.OUTGOING) {
            _logger.info("DIALING");
        } else if (state === _callStates.INCOMING) {
            _logger.info("INCOMING");
        } else if (state === _callStates.JOINED) {

        }
    }

    /**
     * @method _handleOutgoingCallStateChange
     * Handles outgoing call state changes
     * @param {Call} call The call object
     * @param {State} state The state of the call
     * @private
     */
    function _handleOutgoingCallStateChange(call, state) {
        var callId = call.getId(),
            hold_state;

        _currentCall = call;
        _callId = callId;
        _isIncoming = false;
        _isOutgoing = true;

        hold_state = call.getHoldState();
        if (hold_state === "REMOTE_HOLD") {
            _logger.info('CALL HELD REMOTELY');
        }

        if (state === _callStates.IN_CALL) {
            if (hold_state === "LOCAL_HOLD") {
                _logger.info('ON HOLD');
            } else {
                _logger.info('ON CALL');
            }

            if (_currentCall.canSendVideo()) {
                _startIntraFrame();
            }
            _audio.ringOut.pause();
            _audio.ringIn.pause();

            _fireEvent('oncall', call);
        } else if (state === _callStates.RENEGOTIATION) {

        } else if (state === _callStates.ON_HOLD) {
            _logger.info("CALL HELD REMOTELY");
        } else if (state === _callStates.RINGING) {
            _logger.info("RINGING");
        } else if (state === _callStates.ENDED) {
            if (call) {
                _stopIntraFrame();
                if (call.statusCode === 0 || call.statusCode === undefined) {
                    _logger.info("CALL END");
                } else {
                    if ((call.statusCode >= 100 && call.statusCode <= 300)) {
                        _logger.error("WebRTC ERROR");
                    } else {
                        _logger.error("ERROR");
                    }
                }

                if (_isAnonymous && _isOutgoing) {
                    me.logout();
                }

                _currentCall = null;
                _initialize();
                _fireEvent('callended', call);

            }
        } else if (state === _callStates.REJECTED) {
            _logger.info("REJECTED");
        } else if (state === _callStates.OUTGOING) {
            _logger.info("DIALING");
        } else if (state === _callStates.INCOMING) {
            _logger.info("INCOMING");
        } else if (state === _callStates.JOINED) {
            _logger.info('JOINED');
        }
    }

    /**
     * @method _handlePresenceNotification
     * Handles presence notifications, fires the presencenotification event
     * @param {Presence} presence The Presence object
     * @private
     */
    function _handlePresenceNotification(presence) {
        if (presence.state === null) {
            _logger.info("State is empty.");
            return;
        }

        if (presence.name === null) {
            _logger.info("Name is empty.");
            return;
        }
        _fireEvent('presencenotification', presence.name, presence.state, _presenceTypes[presence.state], presence.activity);
    }

    /**
     * @method _handleImNotification
     * Handles incoming call notifications
     * @param {Msg} msg The msg object
     * @private
     */
    function _handleImNotification(msg) {
        _audio.msgIn.play();

        _fireEvent('messagereceived', msg.primaryContact, msg.msgText);
    }

    /**
     * @method _initialize
     * @private
     * Resets local variables and clears any videos
     */
    function _initialize() {
        _isMuted = false;
        _held = false;
        _videoStatus = false;
        _intraframe = null;
        _isAnonymous = false;
        _isIncoming = false;
        _isOutgoing = false;

        _audio.ringIn.pause();
        _audio.ringOut.pause();
        _audio.msgIn.pause();

        _callType = null;

        $('#kandyLocalVideo').remove();
        $('#kandyRemoteVideo').remove();
    }

    /**
     * @method _supportsLocalStorage
     * @private
     * Checks if local storage is available
     */
    function _supportsLocalStorage() {
        try {
            return 'localStorage' in window && window['localStorage'] !== null;
        } catch (e) {
            return false;
        }
    }

    /**
     * @method _setUserInformationLocalStorage
     * @private
     * @param password Password to set
     * Set access token in local storage
     */
    function _setUserInformationLocalStorage(password) {
        localStorage['kandyphone.userinformation'] = _domainApiKey + ';' + _username + ';' + password;
        return true;
    }

    /**
     * @method _getUserInformationLocalStorage
     * @private
     * Get access token from local storage
     */
    function _getUserInformationLocalStorage() {
        return localStorage['kandyphone.userinformation'];
    }

    /**
     * @method _clearAccessTokeLocalStorage
     * @private
     * Clears access token from local storage
     */
    function _clearAccessTokeLocalStorage() {
        localStorage.removeItem('kandyphone.userinformation');
        return true;
    }

    /**
     * @method _login
     * @private
     * Logs in to Experius and SPiDR through fcs JSL
     * @param {String} userAccessToken Access token for user.
     * @param {String} password Password for user.
     */
    function _login(userAccessToken, password) {
        me.setAccessToken(userAccessToken);
        me.getDevices(userAccessToken,
            function (data) {
                if (data.devices && data.devices.length > 0) {
                    me.setUserDeviceId(data.devices[0].id);
                }
            },
            function (message) {
                // log it and keep moving, don't fail the login for this
                _logger.error("login failed: error retrieving device id: " + message);
            }
        );
        me.getLimitedUserDetails(userAccessToken,
            function (data) {
                var username = data.full_user_id;

                fcs.setUserAuth(username, password);

                fcs.notification.start(function () {
                        _username = username;
                        _callStates = fcs.call.States;

                        // make sure the browser supports WebRTC
                        fcs.call.initMedia(function () {
                                _logger.info("media initiated");
                                _mediaInitiated = true;

                                fcs.call.onSetReceiveVideo = function (receiveVideo) {
                                    _logger.error("onSetReceiveVideo=" + (receiveVideo ? 'true' : 'false'));
                                    _fireEvent('receivingvideoset', receiveVideo);
                                };

                                fcs.call.onReceived = function (call) {
                                    _logger.info("incoming call");
                                    _currentCall = call;

                                    _currentCall.onStateChange = function (state) {
                                        _handleIncomingCallStateChange(_currentCall, state);
                                    };

                                    _currentCall.onStreamAdded = function (streamURL) {
                                        _toggleNativeRemoteVideo(_currentCall, streamURL);
                                    };

                                    _handleCallNotification(_currentCall);
                                };

                                fcs.im.onReceived = function (msg) {
                                    _handleImNotification(msg);
                                };

                                fcs.presence.onReceived = function (presence) {
                                    _handlePresenceNotification(presence);
                                };

                                // if the browser supports local storage persist the Access Token
                                if (_config['allowAutoLogin'] && _supportsLocalStorage()) {
                                    _setUserInformationLocalStorage(password);
                                }

                                // add unload event to end any calls
                                window.addEventListener("beforeunload", function (event) {
                                    me.endCall();
                                });

                                _fireEvent('loginsuccess');
                            },
                            function (error) {
                                _logger.error("Problem occurred while initiating media");

                                switch(error) {
                                    case fcs.call.MediaErrors.WRONG_VERSION : // Alert
                                        _logger.error("Media Plugin Version Not Supported");
                                        break;
                                    case fcs.call.MediaErrors.NEW_VERSION_WARNING : //Warning
                                        _logger.error("New Plugin Version is available");
                                        break;
                                    case fcs.call.MediaErrors.NOT_INITIALIZED : // Alert
                                        _logger.error("Media couldn't be initialized");
                                        break;
                                    case fcs.call.MediaErrors.NOT_FOUND : // Alert
                                        _logger.error("Plugin couldn't be found!");
                                        _promptPluginDownload();
                                        break;
                                }
                            }, {
                                "pluginLogLevel": _config.spidrEnv.pluginLogLevel,
                                "videoContainer": _config.spidrEnv.videoContainer,
                                "pluginMode": _config.spidrEnv.pluginMode,
                                "iceserver": _config.spidrEnv.iceserver,
                                "webrtcdtls": _config.spidrEnv.webrtcdtls
                            }
                        );
                    },
                    function () {
                        _logger.error("login failed");

                        _fireEvent('loginfailed');
                    },
                    false
                );
            },
            function () {
                _logger.error("login failed");
                _fireEvent('loginfailed');
            }
        );
    }

    function _UUIDv4() {
        var s = [],
            itoh = '0123456789ABCDEF';

        // Make array of random hex digits. The UUID only has 32 digits in it, but we
        // allocate an extra items to make room for the '-'s we'll be inserting.
        for (var i = 0; i < 36; i++) s[i] = Math.floor(Math.random() * 0x10);

        // Conform to RFC-4122, section 4.4
        s[14] = 4; // Set 4 high bits of time_high field to version
        s[19] = (s[19] & 0x3) | 0x8; // Specify 2 high bits of clock sequence

        // Convert to hex chars
        for (var i = 0; i < 36; i++) s[i] = itoh[s[i]];

        // Insert '-'s
        s[8] = s[13] = s[18] = s[23] = '-';

        return s.join('');
    }

    /**
     * @method _promptPluginDownload
     * @private
     * Prompts the user to download Browser Plugin to support WebRTC
     */
    function _promptPluginDownload() {
        var result = window.confirm("This browser requires a plugin to support WebRTC.  Download plugin?");
        if (result) {
            var os = "Unknown OS";
            if (navigator.appVersion.indexOf("Win") != -1) os = "windows";
            if (navigator.appVersion.indexOf("Mac") != -1) os = "mac";
            if (navigator.appVersion.indexOf("X11") != -1) os = "unix";
            if (navigator.appVersion.indexOf("Linux") != -1) os = "linux";

            if(os == 'windows'){
                // check if this is 64 bit or 32 bit
                if (navigator.userAgent.indexOf("WOW64") != -1 ||
                    navigator.userAgent.indexOf("Win64") != -1 ){
                    window.open('https://kandy-portal.s3.amazonaws.com/public/Kandy/Enabler-Plugins-2.1.376/GCFWEnabler_x86_64__2.1.376.0.exe','target=_blank');
                } else {
                    window.open('https://kandy-portal.s3.amazonaws.com/public/Kandy/Enabler-Plugins-2.1.376/GCFWEnabler_2.1.376.0.exe','target=_blank');
                }
            }
            else if(os == 'mac' || os == 'unix'){
                window.open('https://kandy-portal.s3.amazonaws.com/public/Kandy/Enabler-Plugins-2.1.376/GCFWEnabler_2.1.376.0.pkg','target=_blank');
            }
            else{
                window.alert('Your OS is currently not supported.  We will be providing support shortly');
            }
        }
    }

    me.setAccessToken = function (token) {
        // set AccessToken in fcs library
        // comment out for now because we don't send with every request
        //fcs.setAccessToken(token);
        _userAccessToken = token;
    };

    me.setUserDeviceId = function (deviceId) {
        _userDeviceId = deviceId;
    };

    /**
     * @method setup
     * Setup Spdir
     * @param {Object} config Configuration.
     * @param {Array} [config.listeners={}] callback methods for KandyAPI events (see Events).
     * @param {String} [config.mediatorUrl="http://54.187.112.97:8080/kandywrapper-1.0-SNAPSHOT"] Rest endpoint for KandyWrapper.
     * @param {String} [config.allowAutoLogin=true] True to persist login information in local storage and auto login during setup
     * @param {String} [config.ringInAudioSrcs=['https://kandy-portal.s3.amazonaws.com/public/sounds/ringin.mp3', 'https://kandy-portal.s3.amazonaws.com/public/sounds/ringin.ogg'] Ring tone media sources.
     * @param {String} [config.ringOutAudioSrcs=['https://kandy-portal.s3.amazonaws.com/public/sounds/ringout.mp3', 'https://kandy-portal.s3.amazonaws.com/public/sounds/ringout.ogg'] Ring tone media sources.
     * @param {String} [config.msgInAudioSrcs=['https://kandy-portal.s3.amazonaws.com/public/sounds/msgin.mp3', 'https://kandy-portal.s3.amazonaws.com/public/sounds/msgin.ogg'] Ring tone media sources.
     * @param {Object} [config.fcsConfig] FCS Configuration
     * @param {KandyAPI.NOTIFICATION_TYPES} [config.fcsConfig.notificationType=KandyAPI.NOTIFICATION_TYPES.WEBSOCKET] Type of connection to use for notifications.
     * @param {String} [config.fcsConfig.restUrl="kandysimplexlb-231480754.us-east-1.elb.amazonaws.com"] Rest endpoint for spidr.
     * @param {String} [config.fcsConfig.cors=true] True to enable CORS support.
     * @param {String} [config.fcsConfig.restPort="443"] Port to use for rest endpoint.
     * @param {String} [config.fcsConfig.websocketIP="kandysimplexlb-231480754.us-east-1.elb.amazonaws.com"] Websocket endpoint for spidr.
     * @param {String} [config.fcsConfig.websocketPort="8581"] Port to use for websocket endpoint.
     * @param {String} [config.fcsConfig.disableNotifications=null] True to disable notifications.
     * @param {String} [config.fcsConfig.protocol="https"] Protocol to use http | https.
     * @param {Object} [config.spidrEnv] SPiDR Configuration.
     * @param {Object} [config.spidrEnv.iceserver="stun:206.165.51.23:3478"]
     * @param {Object} [config.spidrEnv.webrtcdtls=null]
     * @param {Object} [config.spidrEnv.videoContainer=""]
     * @param {Object} [config.spidrEnv.pluginMode="auto"]
     * @param {Object} [config.spidrEnv.pluginLogLevel=2]
     * @param {Object} [config.spidrEnv.ice="STUN " + "stun:206.165.51.23:3478"]
     */
    me.setup = function (config) {
        
		fcs.logManager.initLogging(function(x,y,z) { 
            if (z.message === 'ERROR')
                window.console.log(z.message); 
            else
                window.console.log(z.message); 
        }, true)
        _logger = fcs.logManager.getLogger("kandy_sap_js");

		var defaultConfigFcsConfig = _config.fcsConfig;
        var defaultSpidrEnvConfig = _config.spidrEnv;

        // apply default configuration
        _config = $.extend(_config, config);

        // apply default FCS configuration
        _config.fcsConfig = $.extend(defaultConfigFcsConfig, (config.fcsConfig || {}));

        // apply default SPiDR configuration
        _config.spidrEnv = $.extend(defaultSpidrEnvConfig, (config.spidrEnv || {}));

        // setup listeners
        if (_config['listeners']) {
            for (var listener in _config['listeners']) {
                if (me.events[listener] !== undefined)
                    me.events[listener] = _config['listeners'][listener];
            }
        }

        _audio = {
            ringIn: $('<audio/>', {
                loop: 'loop',
                id: 'ringInAudio'
            })[0],
            ringOut: $('<audio/>', {
                loop: 'loop',
                id: 'ringOutAudio'
            })[0],
            msgIn: $('<audio/>', {
                id: 'msgInAudio'
            })[0]
        };

        // setup RingIn sources
        for (var i=0; i < _config.ringInAudioSrcs.length; i++) {
            _audio.ringIn.appendChild($('<source/>', _config.ringInAudioSrcs[i])[0]);
        }

        // setup RingOut sources
        for (var i=0; i < _config.ringOutAudioSrcs.length; i++) {
            _audio.ringOut.appendChild($('<source/>', _config.ringOutAudioSrcs[i])[0]);
        }

        // setup Msg sources
        for (var i=0; i < _config.msgInAudioSrcs.length; i++) {
            _audio.msgIn.appendChild($('<source/>', _config.msgInAudioSrcs[i])[0]);
        }

        _audio.msgIn.pause();
        _audio.ringIn.pause();
        _audio.ringOut.pause();

        _audio.msgIn.pause();
        _audio.ringIn.pause();
        _audio.ringOut.pause();

        _logger = fcs.logManager.getLogger();

        // setup SPiDR with default configuration, override with passed configuration
        fcs.setup(_config.fcsConfig);

        fcs.notification.setOnConnectionLost(function () {
            _logger.info('Connection Lost');
        });

        if (_config['allowAutoLogin'] && _supportsLocalStorage() && _getUserInformationLocalStorage()) {
            me.login(_getUserInformationLocalStorage().split(';')[0],
                _getUserInformationLocalStorage().split(';')[1],
                _getUserInformationLocalStorage().split(';')[2]);
        }
    };

    /**
     * @method login
     * Logon
     * @param {String} domainApiId The domain API ID.
     * @param {String} username The username (should not include '@<domain>').
     * @param {String} password The password.
     */
    me.login = function (domainApiId, username, password) {
        // if username has domain in it remove it
        username = username.split('@')[0];

        me.getUserAccessToken(domainApiId, username, password,
            function (userAccessToken) {
                _domainApiKey = domainApiId;
                _login(userAccessToken, password);
            },
            function () {
                _logger.error("login failed");

                // if the browser supports local storage clear out the stored access token if there was one stored
                if (_supportsLocalStorage()) {
                    _clearAccessTokeLocalStorage();
                }

                _fireEvent('loginfailed');
            });
    };

    /**
     * @method logout
     * Logs out
     * @param {Function} success The success callback.
     */
    me.logout = function (success) {
        _logger.info('logging out');

        me.updatePresence(11);

        // if the browser supports local storage clear out the stored access token
        if (_supportsLocalStorage()) {
            _clearAccessTokeLocalStorage();
        }

        fcs.clearResources(function () {
            _initialize();
            if (success)
                success();
        }, true, true);
    };

    /**
     * @method hasStoredLogin
     * Returns true if login information has been stored in local storage
     */
    me.hasStoredLogin = function () {
        (_supportsLocalStorage() && _getUserInformationLocalStorage());
    };

    /**
     * @method isMediaInitialized
     * Returns true if media is initialized
     */
    me.isMediaInitiated = function () {
        return _mediaInitiated;
    };

    /**
     * @method showVideo
     * Returns true if video should be shown
     */
    me.showVideo = function () {
        return _videoStatus;
    };

    /**
     * @method isPSTNCall
     * Returns true if calls origination was PSTN
     */
    me.isPSTNCall = function () {
        return (_currentCall && _currentCall.callerName == 'Private Name' && _currentCall.callerNumber == 'Private Number');
    };

    /**
     * @method isIncoming
     * Returns true if call is incoming
     */
    me.isIncoming = function () {
        return _isIncoming;
    };

    /**
     * @method isOutgoing
     * Returns true if call is outgoing
     */
    me.isOutgoing = function () {
        return _isOutgoing;
    };

    /**
     * @method callTypes
     * Gets call types
     * See call types enumeration
     */
    me.callTypes = function () {
        return _callTypes;
    };

    /**
     * @method isAnonymous
     * returns if the call is anonymous
     */
    me.isAnonymous = function () {
        return _isAnonymous;
    };

    /**
     * @method getAnonymousData
     * returns anonymous data if the call is anonymous null if not.
     */
    me.getAnonymousData = function () {
        if (_currentCall && _isAnonymous) {
            return _currentCall.callerName
        } else {
            return null;
        }
    };

    /**
     * @method callType
     * Gets current call type
     */
    me.callType = function () {
        return _callType;
    };

    /**
     * @method makePSTNCall
     * Starts PSTN call using the configured pstnOutNumber
     * @param {String} number The number to call.
     */
    me.makePSTNCall = function (number) {
        me.makeVoiceCall(_config.pstnOutNumber + number);
    };

    /**
     * @method makeVoiceCall
     * Starts call
     * @param {String} number The number to call.
     */
    me.makeVoiceCall = function (number) {
        _logger.info('making voice call');
        if (number == _username) {
            _fireEvent('callinitiatefailure', 'You cannot call yourself');
            return;
        }

        fcs.call.startCall(fcs.getUser(), '', number,
            //onSuccess
            function (outgoingCall) {
                outgoingCall.onStateChange = function (state, statusCode) {
                    outgoingCall.statusCode = statusCode;

                    _handleOutgoingCallStateChange(outgoingCall, state);
                };

                outgoingCall.onStreamAdded = function (streamURL) {
                    _toggleNativeRemoteVideo(outgoingCall, streamURL);
                };

                _videoStatus = false;
                _isAnonymous = false;
                _currentCall = outgoingCall;

                _audio.ringOut.play();

                _fireEvent('callinitiated', _currentCall, number);
            },
            //onFailure
            function () {
                _logger.error("call failed");
                _fireEvent('callinitiatefailure', '');

            },
            false, false);
    };

    /**
     * @method makeVideoCall
     * Starts call
     * @param {String} number The number to call.
     */
    me.makeVideoCall = function (number) {
        _logger.info('making video call');

        if (number == _username) {
            _fireEvent('callinitiatefailure', 'You cannot call yourself');
            return;
        }

        fcs.call.startCall(fcs.getUser(), '', number,
            //onSuccess
            function (outgoingCall) {
                outgoingCall.onStateChange = function (state, statusCode) {
                    outgoingCall.statusCode = statusCode;

                    _handleOutgoingCallStateChange(outgoingCall, state);
                };

                outgoingCall.onStreamAdded = function (streamURL) {
                    _toggleNativeRemoteVideo(outgoingCall, streamURL);
                };

                _videoStatus = true;
                _isAnonymous = false;
                _currentCall = outgoingCall;
                _setLocalStream(_currentCall.localStreamURL);
                _audio.ringOut.play();

                _fireEvent('callinitiated', _currentCall, number);
            },
            //onFailure
            function () {
                _logger.error("call failed");
                _fireEvent('callinitiatefailure', '');
            },
            false, true);
    };

    /**
     * @method makeAnonymousVideoCall
     * Starts Anonymous video call
     * @param {String} number The kandy user to make the anonymous call to.
     * @param {String} anonymousData Anonymous data.
     * @param {String} caller_user_name The Kandy user making the call (caller).
     */
    me.makeAnonymousVideoCall = function (number, anonymousData, caller_user_name) {
        fcs.setup(_config.fcsConfig);

        //Setup user credential
        fcs.setUserAuth(number, '');

        fcs.notification.start(function () {
            _logger.info("Login succesfully");

            _callStates = fcs.call.States;

            fcs.call.initMedia(function () {
                _logger.info("Call init successfully");
                setTimeout(function () {
                    _initialize();

                    var anonymousUserName = {
                        "firstName": anonymousData
                    };

                    caller_user_name = caller_user_name || 'anonymous@concierge.com';

                    fcs.call.startCall(caller_user_name, anonymousUserName, number,
                        //onSuccess
                        function (outgoingCall) {
                            outgoingCall.onStateChange = function (state, statusCode) {
                                outgoingCall.statusCode = statusCode;

                                _handleOutgoingCallStateChange(outgoingCall, state);
                            };

                            outgoingCall.onStreamAdded = function (streamURL) {
                                _toggleNativeRemoteVideo(outgoingCall, streamURL);
                            };

                            _videoStatus = true;
                            _isAnonymous = true;
                            _currentCall = outgoingCall;

                            _audio.ringOut.play();

                            _fireEvent('callinitiated', _currentCall, number);
                        },
                        //onFailure
                        function () {
                            _logger.error("call failed");
                            _fireEvent('callinitiatefailure');

                        },
                        false, true);

                }, 500);
            }, function () {
                console.error("Call init failed");
                logout();
            }, {
                "pluginLogLevel": _config.spidrEnv.pluginLogLevel,
                "ice": _config.spidrEnv.ice,
                "videoContainer": _config.spidrEnv.videoContainer,
                "pluginMode": _config.spidrEnv.pluginMode,
                "iceserver": _config.spidrEnv.iceserver,
                "webrtcdtls": _config.spidrEnv.webrtcdtls
            });
        }, function () {
            console.error("Login failed");
        }, true);
    };

    /**
     * @method makeAnonymousVoiceCall
     * Starts Anonymous voice call
     * @param {String} callee_user_name The Kandy user to make the anonymous call to (callee).
     * @param {String} anonymousData Anonymous data to be sent to the callee.
     * @param {String} caller_user_name The Kandy user making the call (caller).
     */
    me.makeAnonymousVoiceCall = function (callee_user_name, anonymousData, caller_user_name) {
        fcs.setup(_config.fcsConfig);

        //Setup user credential
        fcs.setUserAuth(callee_user_name, '');

        fcs.notification.start(function () {
            _logger.info("Login succesfully");

            _callStates = fcs.call.States;

            fcs.call.initMedia(function () {
                _logger.info("Call init successfully");
                setTimeout(function () {
                    _initialize();

                    var anonymousUserName = {
                        "firstName": anonymousData
                    };

                    caller_user_name = caller_user_name || 'anonymous@concierge.com';

                    fcs.call.startCall(caller_user_name, anonymousUserName, callee_user_name,
                        //onSuccess
                        function (outgoingCall) {
                            outgoingCall.onStateChange = function (state, statusCode) {
                                outgoingCall.statusCode = statusCode;

                                _handleOutgoingCallStateChange(outgoingCall, state);
                            };

                            outgoingCall.onStreamAdded = function (streamURL) {
                                _toggleNativeRemoteVideo(outgoingCall, streamURL)
                            };

                            _videoStatus = false;
                            _isAnonymous = true;
                            _currentCall = outgoingCall;

                            _audio.ringOut.play();

                            _fireEvent('callinitiated', _currentCall, callee_user_name);
                        },
                        //onFailure
                        function () {
                            _logger.error("call failed");
                            _fireEvent('callinitiatefailure');

                        },
                        false, false);

                }, 500);
            }, function () {
                _logger.error("Call init failed");
                logout();
            }, {
                "pluginLogLevel": _config.spidrEnv.pluginLogLevel,
                "ice": _config.spidrEnv.ice,
                "videoContainer": _config.spidrEnv.videoContainer,
                "pluginMode": _config.spidrEnv.pluginMode,
                "iceserver": _config.spidrEnv.iceserver,
                "webrtcdtls": _config.spidrEnv.webrtcdtls
            });
        }, function () {
            _logger.error("Login failed");
        }, true);
    };

    /**
     * @method rejectCall
     * reject incoming
     */
    me.rejectCall = function () {
        _currentCall.reject(
            function () {
                _audio.ringIn.pause();

                _fireEvent('callrejected');
            },
            function () {
                _audio.ringIn.pause();
                _logger.info("reject failed");
            }
        );
    };

    /**
     * @method answerVoiceCall
     * Answer voice call
     */
    me.answerVoiceCall = function () {
        _audio.ringIn.pause();
        _currentCall.answer(function () {
                if (_videoStatus) {
                    _startIntraFrame();
                }

                _setLocalStream(_currentCall.localStreamURL);

                _fireEvent('callanswered', _currentCall, _isAnonymous);
            },
            function () {
                _audio.ringIn.pause();
                _logger.info("voice answer failed");

                _fireEvent('callanswerfailure');
            },
            false
        );
    };

    /**
     * @method answerVideoCall
     * Answer video call
     */
    me.answerVideoCall = function () {
        _audio.ringIn.pause();
        _currentCall.answer(function () {
                if (_videoStatus) {
                    _startIntraFrame();
                }

                _setLocalStream(_currentCall.localStreamURL);

                _fireEvent('callanswered', _currentCall, _isAnonymous);
            },
            function () {
                _audio.ringIn.pause();
                _logger.info("video answer failed");

                _fireEvent('callanswerfailure');
            },
            true
        );
    };

    /**
     * @method muteCall
     * Mutes current call
     */
    me.muteCall = function () {
        if (_currentCall) {
            _currentCall.mute();
            _isMuted = true;
        }
    };

    /**
     * @method unMuteCall
     * Unmutes current call
     */
    me.unMuteCall = function () {
        if (_currentCall) {
            _currentCall.unmute();
            _isMuted = false;
        }
    };

    /**
     * @method holdCall
     * Holds current call
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     */
    me.holdCall = function (success, failure) {
        if (_currentCall) {
            success = success || function () {
            };
            failure = failure || function () {
            };

            _currentCall.hold(success, failure);
            _held = true;
        }
    };

    /**
     * @method unHoldCall
     * Removes hold on current call
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     */
    me.unHoldCall = function (success, failure) {
        if (_currentCall) {
            success = success || function () {
            };
            failure = failure || function () {
            };

            _currentCall.unhold(success, failure);
            _held = false;
        }
    };

    /**
     * @method startCallVideo
     * Starts video on call
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     */
    me.startCallVideo = function (success, failure) {
        if (_currentCall) {
            success = success || function () {
            };
            failure = failure || function () {
            };

            _currentCall.videoStart(success, failure);
        }
    };

    /**
     * @method stopCallVideo
     * Stops video on call
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     */
    me.stopCallVideo = function (success, failure) {
        if (_currentCall) {
            success = success || function () {
            };
            failure = failure || function () {
            };

            _currentCall.stopVideo(success, failure);
        }
    };

    /**
     * @method endCall
     * Ends call
     */
    me.endCall = function () {
        if (_currentCall) {
            _logger.info('ending call');

            _currentCall.end(
                function () {
                    _stopIntraFrame();
                    _currentCall = null;

                    if (_isAnonymous && _isOutgoing) {
                        fcs.clearResources(function () {
                        }, true, true);
                    }

                    _initialize();

                    _fireEvent('callended', _currentCall);

                },
                function () {
                    _logger.error('COULD NOT END CALL');

                    _fireEvent('callendfailure');
                }
            );
        }
    };

    /**
     * @method watchPresence
     * Sets up watching for presence change of contacts.
     */
    me.watchPresence = function (list) {
        var contactList = [];

        fcs.presence.watch(list.map(function (item) {
                return item.full_user_id
            }),
            function () {
                _logger.info('Watch presence successful');
            },
            function () {
                _logger.error('Watch presence error');
            }
        );
    };

    /**
     * @method updatePresence
     * Sets presence for logged in user.
     */
    me.updatePresence = function (status) {
        if (fcs.getServices().presence === true) {
            fcs.presence.update(parseInt(status),
                function () {
                    _logger.info("Presence update success");
                },
                function () {
                    _logger.error("Presence update failed");
                });
        } else {
            _logger.error("Presence service not available for account");
        }
    };

    /**
     * @method searchDirectoryByPhoneNumber
     * @param {String} phoneNumber The name to search for.
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     * Search directory for user.
     */
    me.searchDirectoryByPhoneNumber = function (phoneNumber, success, failure) {
        fcs.addressbook.searchDirectory(phoneNumber, fcs.addressbook.SearchType.PHONENUMBER, success, failure);
    };

    /**
     * @method searchDirectoryByName
     * @param {String} name The name to search for
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     * Search directory for user.
     */
    me.searchDirectoryByName = function (name, success, failure) {
        fcs.addressbook.searchDirectory(name, fcs.addressbook.SearchType.NAME, success, failure);
    };

    /**
     * @method searchDirectoryByUserName
     * @param {String} username Username to search for.
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     * Search directory for user.
     */
    me.searchDirectoryByUserName = function (username, success, failure) {
        fcs.addressbook.searchDirectory(username, fcs.addressbook.SearchType.USERNAME, success, failure);
    };

    /**
     * @method retrievePersonalAddressBook
     * @param {String} userAccessToken
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     * Retrieves address book entries.
     */
    me.retrievePersonalAddressBook = function (success, failure) {
        var paramStr = 'key=' + encodeURIComponent(_userAccessToken);

        $.ajax({
            url: _config.kandyApiUrl + '/users/addressbooks/personal?' + paramStr,
            crossDomain: true,
            beforeSend: function (xhrObj) {
                xhrObj.setRequestHeader("Content-Type", "application/json");
                xhrObj.setRequestHeader("Accept", "application/json");
            },
            success: function (response) {
                if (response.status === responseCodes.OK) {
                    if (success) {
                        success(response.result.contacts);
                    }
                } else if (failure) {
                    failure(response.message, response.status);
                }
            },
            error: function (x, e) {
                if (failure) {
                    failure(x.statusText, responseCodes.ajaxError);
                }
            }
        });
    };

    /**
     * @method addToPersonalAddressBook
     * @param {String} userAccessToken
     * @param {Object} entry Object container properties of the entry to add.
     * @param {Object} entry.username Object container properties of the entry to add.
     * @param {Object} entry.nickname  Nickname for address book entry.
     * @param {Object} [entry.firstName] first name for address book entry.
     * @param {Object} [entry.lastName] last name for address book entry.
     * @param {Object} [entry.homePhone] home phone for address book entry.
     * @param {Object} [entry.mobileNumber] mobile number for address book entry.
     * @param {Object} [entry.businessPhone] business phone for address book entry.
     * @param {Object} [entry.fax] fax for address book entry.
     * @param {Object} [entry.email] email for address book entry.
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     * Adds kandy user to current kandy user's address book.
     */
    me.addToPersonalAddressBook = function (entry, success, failure) {
        var paramStr = 'key=' + encodeURIComponent(_userAccessToken);

        $.ajax({
            type: 'POST',
            url: _config.kandyApiUrl + '/users/addressbooks/personal?' + paramStr,
            data: JSON.stringify({contact: entry}),
            crossDomain: true,
            beforeSend: function (xhrObj) {
                xhrObj.setRequestHeader("Content-Type", "application/json");
                xhrObj.setRequestHeader("Accept", "application/json");
            },
            success: function (response) {
                if (response.status === responseCodes.OK) {
                    if (success) {
                        success();
                    }
                } else if (failure) {
                    failure(response.message, response.status);
                }
            },
            error: function (x, e) {
                if (failure) {
                    failure(x.statusText, responseCodes.ajaxError);
                }
            }
        });
    };

    /**
     * @method removeFromPersonalAddressBook
     * @param {String} contactId Contact ID for the contact.
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     * Retrieves address book entries.
     */
    me.removeFromPersonalAddressBook = function (contactId, success, failure) {
        var paramStr = 'key=' + encodeURIComponent(_userAccessToken) +
            '&contact_id=' + encodeURIComponent(contactId);

        $.ajax({
            type: 'DELETE',
            url: _config.kandyApiUrl + '/users/addressbooks/personal?' + paramStr,
            crossDomain: true,
            beforeSend: function (xhrObj) {
                xhrObj.setRequestHeader("Accept", "*/*");
                xhrObj.setRequestHeader("Content-Type", "text/html");
            },
            success: function (response) {
                if (response.status === responseCodes.OK) {
                    if (success) {
                        success();
                    }
                } else if (failure) {
                    failure(response.message, response.status);
                }
            },
            error: function (x, e) {
                if (failure) {
                    failure(x.statusText, responseCodes.ajaxError);
                }
            }
        });
    };

    /**
     * @method sendIm
     * @param {String} user Username of message recipient
     * @param {String} text Textual message to be sent to recipient
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     * Sends a textual instant message to another Kandy user
     */
    me.sendIm = function (user, text, success, failure) {
        if (_config.messageProvider === 'fring') {
            var uuid = _UUIDv4();
            var message = {
                "message": {
                    "messageType": "chat",
                    "contentType": "text",
                    "destination": user,
                    "UUID": uuid,
                    "message": {
                        "mimeType": "text/plain",
                        "text": text
                    }
                }
            };

            $.ajax({
                type: 'POST',
                url: _config.kandyApiUrl + '/devices/messages?key=' + _userAccessToken + '&device_id=' + _userDeviceId,
                crossDomain: true,
                beforeSend: function (xhrObj) {
                    xhrObj.setRequestHeader("Content-Type", "application/json");
                    xhrObj.setRequestHeader("Accept", "application/json");
                },
                data: JSON.stringify(message),
                success: function (response) {
                    if (response.status === responseCodes.OK) {
                        if (success) {
                            success();
                        }
                    } else if (failure) {
                        failure(response.message, response.status);
                    }
                },
                error: function (x, e) {
                    if (failure)
                        failure(x.statusText, responseCodes.ajaxError);
                }
            });
            return uuid;
        } else if (_config.messageProvider === 'spidr') {
            var im = new fcs.im.Message();
            im.primaryContact = user;
            im.type = "A2";
            im.msgText = text;
            im.charset = "UTF-8";

            fcs.im.send(im, success, failure);
            return 0;
        }
    };

    /**
     * @method sendImWithFile
     * @param {String} user Username of message recipient
     * @param {Object} file File to be sent
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     * Sends a File message to another Kandy user
     */
    me.sendImWithFile = function (user, file, success, failure) {
        if (_config.messageProvider === 'fring') {
            var uuid = _UUIDv4();
            // Upload file and if we get a success send the IM
            me.uploadFile(file, function (fileUuid) {
                var message = {
                    "message": {
                        "messageType": "chat",
                        "contentType": "text",
                        "destination": user,
                        "UUID": uuid,
                        "message": {
                            "mimeType": file.type,
                            "content_uuid": fileUuid,
                            "content_name": file.name
                        }
                    }
                };
                $.ajax({
                    type: 'POST',
                    url: _config.kandyApiUrl + '/devices/messages?key=' + _userAccessToken + '&device_id=' + _userDeviceId,
                    crossDomain: true,
                    beforeSend: function (xhrObj) {
                        xhrObj.setRequestHeader("Content-Type", "application/json");
                        xhrObj.setRequestHeader("Accept", "application/json");
                    },
                    data: JSON.stringify(message),
                    success: function (response) {
                        if (response.status === responseCodes.OK) {
                            if (success) {
                                success();
                            }
                        } else if (failure) {
                            failure(response.message, response.status);
                        }
                    },
                    error: function (x, e) {
                        if (failure)
                            failure(x.statusText, responseCodes.ajaxError);
                    }
                });
            });

            return uuid;
        }
        else {
            _logger.error('NOT SUPPORTED');
        }
    };

    /**
     * @method uploadFile
     * @param {File} file File to be sent
     * @param {Function} success The success callback.
     * @param {UUID} success.uuid The UUID of the uploaded file.
     * @param {Function} failure The failure callback.
     * @param {string}    failure.message Error Message.
     * @param {string}    failure.statusCode Error status code.
     * Uploads file to be used in Rich IM messaging
     */
    me.uploadFile = function (file, success, failure) {
        // Generate a UUID
        var uuid = _UUIDv4();

        // Create a new FormData object.
        var formData = new FormData();

        // Add the file to the request.
        formData.append('file', file, file.name);

        // Set up the request.
        var xhr = new XMLHttpRequest();

        var url = _config.kandyApiUrl + '/devices/content?key=' + _userAccessToken + '&content_uuid=' + encodeURIComponent(uuid) + '&device_id=' + _userDeviceId + '&content_type=' + encodeURIComponent(file.type);

        // Open the connection.
        xhr.open('POST', url, true);

        // Set up a handler for when the request finishes.
        xhr.onload = function () {
            if (xhr.status === 200) {
                var result = {};
                if (JSON) {
                    result = JSON.parse(xhr.responseText);
                }
                else {
                    result = eval('(' + xhr.responseText + ')');
                }

                if (result.status == responseCodes.OK) {
                    // File(s) uploaded.
                    if (success)
                        success(uuid);

                }
                else if (failure) {
                    if (failure)
                        failure(response.message, response.status);
                }


            } else {
                if (failure)
                    failure('Request Error', '500');
            }
        };

        // Send the Data.
        xhr.send(formData);

        return uuid;
    };

    /**
     * @method buildFileUrl
     * @param {uuid} UUID for file
     * Builds Url to uploaded file
     */
    me.buildFileUrl = function (uuid) {
        return _config.kandyApiUrl + '/devices/content?key=' + _userAccessToken + '&content_uuid=' + encodeURIComponent(uuid) + '&device_id=' + _userDeviceId;
    };

    /**
     * @method buildFileThumbnailUrl
     * @param {uuid} UUID for file
     * @param {string} size of thumbnail 24x24
     * Builds Url to thumbnail uploaded file
     */
    me.buildFileThumbnailUrl = function (uuid, size) {
        if (!size) {
            size = '500x500';
        }

        return _config.kandyApiUrl + '/devices/content/thumbnail?key=' + _userAccessToken + '&content_uuid=' + encodeURIComponent(uuid) + '&device_id=' + _userDeviceId + '&thumbnail_size=' + size;
    };

    /**
     * @method getIm
     * Retrieves IM messages
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     * @return {Object} response An array of messages
     * e.g.
     * {
	 *    [
	 *      {
                "messageType":"chat",
                "sender":
                    {
                        "user_id":"972542205056",
                        "domain_name":"domain.com",
                        "full_user_id":"972542205056@domain.com"
     },
     "UUID":"acd2fa752c3c4edf97de8b0a48f622f0",
     "timestamp":"1400510413",
     "message":
     {
         "mimeType": "text/plain",
         "text": "let's meet tonight"
     }
     }
     *    ]
     * }
     */
    me.getIm = function (success, failure) {
        $.ajax({
            type: 'GET',
            url: _config.kandyApiUrl + '/devices/messages?key=' + _userAccessToken + '&device_id=' + _userDeviceId,
            crossDomain: true,
            beforeSend: function (xhrObj) {
                xhrObj.setRequestHeader("Content-Type", "application/json");
                xhrObj.setRequestHeader("Accept", "application/json");
            },
            success: function (response) {
                var incoming;
                if (response.status === responseCodes.OK) {
                    if (success) {

                        if (response.result.messages.length) {
                            // prepare id list for clearing
                            var id_list = response.result.messages.map(function (item) {
                                return item.UUID;
                            });

                            // make sure UUIDs have hyphens
                            response.result.messages = response.result.messages.map(function (msg) {
                                if (msg.UUID.indexOf('-') === -1) {
                                    msg.UUID = [msg.UUID.substring(0, 8),
                                        msg.UUID.substring(8, 12),
                                        msg.UUID.substring(12, 16),
                                        msg.UUID.substring(16, 20),
                                        msg.UUID.substring(20, msg.UUID.length)
                                    ].join('-');
                                }
                                return msg;
                            });
                        }

                        success(response.result);

                        if (response.result.messages.length) {
                            me.clearIm(id_list);
                        }
                    }
                } else if (failure) {
                    failure(response.message, response.status);
                }
            },
            error: function (x, e) {
                if (failure)
                    failure(x.statusText, responseCodes.ajaxError);
            }
        });
    };

    /**
     * @method clearIm
     * Retrieves IM messages
     * @param {Array} ids Id of IMs to remove.
     * @param {Function} failure The failure callback.
     * @return {Object} response An array of messages
     */
    me.clearIm = function (ids, success, failure) {
        var i = 0,
            encodeddata,
            url,
            xhr;
        for (i; i < ids.length; i += 10) {
            encodeddata = encodeURIComponent('["' + ids.slice(i, i + 10).join('","') + '"]');
            url = _config.kandyApiUrl + '/devices/messages?key=' + _userAccessToken + '&messages=' + encodeddata + '&device_id=' + _userDeviceId;
            xhr = new XMLHttpRequest();
            xhr.open('DELETE', url);
            xhr.send();
        }
    };

    /**
     * @method retrieveDeviceAddressBook
     * Retrieves device address book entries
     * @param {String} deviceId
     * @param {String} userAccessToken
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     * @return {Object} response An array of address book entries
     * e.g. {
	 *       [
	 *         {"number":"972542194671",
	 *          "contactId":"",
	 *          "deviceId":"45f9346d86fe42ae976a2205c275fae0",
	 *          "firstName":"Roy",
	 *          "lastName":"Rousso",
	 *          "hintType":"none"},
	 *         {"number":"4074543322",
	 *          "contactId":"",
	 *          "deviceId":"7fe4146d86fe42ae976a2205c279bc22",
	 *          "firstName":"Jon",
	 *          "lastName":"Albright",
	 *          "hintType":"none"}
	 *       ]
	 */
    me.retrieveDeviceAddressBook = function (deviceId, userAccessToken, success, failure) {

        var paramStr = 'key=' + encodeURIComponent(userAccessToken) +
            '&device_id=' + encodeURIComponent(deviceId);

        $.ajax({
            type: 'GET',
            url: _config.kandyApiUrl + '/devices/addressbooks?' + paramStr,
            crossDomain: true,
            beforeSend: function (xhrObj) {
                xhrObj.setRequestHeader("Content-Type", "application/json");
                xhrObj.setRequestHeader("Accept", "application/json");
            },
            success: function (response) {
                if (response.success) {
                    if (success)
                        success({
                            contacts: response.contacts
                        });
                } else {
                    if (failure)
                        failure(response.message);
                }
            },
            error: function (x, e) {
                if (failure)
                    failure(x.statusText, responseCodes.ajaxError);
            }
        });
    };

    /**
     * @method uploadDeviceAddressBook
     * Upload device address book entries
     * @param {String} deviceId
     * @param {String} userAccessToken
     * @param {Object} contacts An object containing the array of contacts to upload (see e.g.)
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     * @return {Object} response object with success being true or false
     *
     * contacts e.g. [
     *
     *    {
	 * 		"numbers":["123456789"],
	 *		"firstName":"First",
	 *		"lastName":"Last"
	 *	},
     *    {
	 *		"numbers":["987654321"],
	 *		"firstName":"Jon",
	 *		"lastName":"Albright"
	 *	}
     * ]
     */
    me.uploadDeviceAddressBook = function (deviceId, userAccessToken, contacts, success, failure) {

        var paramStr = 'key=' + encodeURIComponent(userAccessToken) +
            '&device_id=' + encodeURIComponent(deviceId);

        $.ajax({
            type: 'POST',
            url: _config.kandyApiUrl + '/devices/addressbooks?' + paramStr,
            crossDomain: true,
            data: JSON.stringify({
                'contacts': contacts
            }),
            beforeSend: function (xhrObj) {
                xhrObj.setRequestHeader("Content-Type", "application/json");
                xhrObj.setRequestHeader("Accept", "application/json");
            },
            success: function (response) {
                if (response.status === responseCodes.OK) {
                    if (success) {
                        success(response.result);
                    }
                } else if (failure) {
                    failure(response.status);
                }
            },
            error: function (x, e) {
                if (failure)
                    failure(x.statusText, responseCodes.ajaxError);
            }
        });
    };

    /**
     * @method deleteDeviceAddressBook
     * Delete device address book entries
     * @param {String} deviceId
     * @param {String} userAccessToken
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     * @return {Object} response object with success being true or false
     * e.g.
     */
    me.deleteDeviceAddressBook = function (deviceId, userAccessToken, success, failure) {

        var paramStr = 'key=' + encodeURIComponent(userAccessToken) +
            '&device_id=' + encodeURIComponent(deviceId);

        $.ajax({
            type: 'DELETE',
            url: _config.kandyApiUrl + '/devices/addressbooks?' + paramStr,
            crossDomain: true,
            beforeSend: function (xhrObj) {
                xhrObj.setRequestHeader("Accept", "application/json");
            },
            success: function (response) {
                if (response.status === responseCodes.OK) {
                    if (success) {
                        success(response);
                    }
                } else if (failure) {
                    failure(response);
                }
            },
            error: function (x, e) {
                if (failure)
                    failure(x.statusText, responseCodes.ajaxError);
            }
        });
    };

    /**
     * @method retrieveAddressBook
     * Retrieve the network address book
     * @param {String} userAccessToken
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     * @return {Object} response object with success being true or false
     */
    me.retrieveAddressBook = function (userAccessToken, success, failure) {
        $.ajax({
            type: 'GET',
            url: _config.kandyApiUrl + '/users/addressbooks/device?key=' + encodeURIComponent(userAccessToken),
            crossDomain: true,
            beforeSend: function (xhrObj) {
                xhrObj.setRequestHeader("Content-Type", "application/json");
                xhrObj.setRequestHeader("Accept", "application/json");
            },
            success: function (response) {
                if (response.status === responseCodes.OK) {
                    if (success) {
                        success(response);
                    }
                } else if (failure) {
                    failure(response);
                }
            },
            error: function (x, e) {
                if (failure)
                    failure(x.statusText, responseCodes.ajaxError);
            }
        });
    };

    /**
     * @method getUserAccessToken
     * Retrieves a user access token
     * @param {String} domainApiId
     * @param {String} userName
     * @param {String} userPassword
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     */
    me.getUserAccessToken = function (domainApiId, userName, userPassword, success, failure) {
        var paramStr = 'key=' + encodeURIComponent(domainApiId) +
            "&user_id=" + encodeURIComponent(userName) +
            "&user_password=" + encodeURIComponent(userPassword);

        $.ajax({
            url: _config.kandyApiUrl + '/domains/users/accesstokens?' + paramStr,
            crossDomain: true,
            beforeSend: function (xhrObj) {
                xhrObj.setRequestHeader("Content-Type", "application/json");
                xhrObj.setRequestHeader("Accept", "application/json");
            },
            success: function (response) {
                if (response.status === responseCodes.OK) {
                    if (success) {
                        success(response.result.user_access_token);
                    }
                } else if (failure) {
                    failure(response.message, response.status);
                }
            },
            error: function (x, e) {
                if (failure) {
                    failure(x.statusText, responseCodes.ajaxError);
                }
            }
        });
    };

    /**
     * @method getLimitedUserDetails
     * Retrieves a user access token
     * @param {String} userAccessToken
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     */
    me.getLimitedUserDetails = function (userAccessToken, success, failure) {
        var paramStr = 'key=' + encodeURIComponent(userAccessToken);

        $.ajax({
            url: _config.kandyApiUrl + '/users/details/limited?' + paramStr,
            crossDomain: true,
            beforeSend: function (xhrObj) {
                xhrObj.setRequestHeader("Content-Type", "application/json");
                xhrObj.setRequestHeader("Accept", "application/json");
            },
            success: function (response) {
                if (response.status === responseCodes.OK) {
                    if (success) {
                        success(response.result.user);
                    }
                } else if (failure) {
                    failure(response.message, response.status);
                }
            },
            error: function (x, e) {
                if (failure) {
                    failure(x.statusText, responseCodes.ajaxError);
                }
            }
        });
    };

    /**
     * @method retrieveDevices
     * Retrieves devices for users
     * @param {Function} userAccessToken User Access Token.
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     */
    me.getDevices = function (userAccessToken, success, failure) {
        var encodedAccessCode = encodeURIComponent(userAccessToken);

        $.ajax({
            type: 'GET',
            url: _config.kandyApiUrl + '/users/devices?key=' + encodedAccessCode,
            crossDomain: true,
            success: function (response) {
                if (response.status === responseCodes.OK) {
                    if (success) {
                        success(response.result);
                    }
                } else if (failure) {
                    failure(response.message, response.status);
                }
            },
            error: function (x, e) {
                _logger.error('ERROR RETRIEVING DEVICES');

                if (failure) {
                    failure(x.statusText, responseCodes.ajaxError);
                }
            }
        });
    };

    return me;
}());

/**
 * @author Russell Holmes
 * KandyAPI.Registration
 * @singleton
 * Registration is used to register with the Kandy API.
 *
 * Simply create a new KandyAPI phone instance passing in your configuration
 *
 *     KandyAPI.Registration.setup({
 *       listeners:{
 *           callinitiated: function(call, number){
 *              // Call has been initiated.
 *           }
 *       }
 *     });
 */
KandyAPI.Registration = (function () {
    var me = {};

    /**
     * @property {Object} _config Configuration for KandyAPI.Registration.
     * @private
     */
    var _config = {
        listeners: {},
        kandyApiUrl: 'https://dev-api.kandy.io'
    };

    /**
     * @property {String} _config Domain Access Code.
     * @private
     */
    var _domainAccessToken = null;

    /**
     * @method _fireEvent
     * Fires passed event
     * @private
     */
    function _fireEvent() {
        var eventName = Array.prototype.shift.apply(arguments);

        if (me.events[eventName])
            me.events[eventName].apply(me, arguments);
    }

    /**
     * @method setup
     * @param {Object} config Configuration.
     * @param {Array} [config.listeners={}] Listeners for KandyAPI.Registration.
     * @param {String} [config.mediatorUrl="http://api.kandy.io"] Rest endpoint for KandyWrapper.
     */
    me.setup = function (config) {

        // setup default configuration
        _config = $.extend(_config, config);

        me._domainAccessToken = config.domainAccessToken;

        // setup listeners
        if (_config['listeners']) {
            for (var listener in _config['listeners']) {
                if (me.events[listener] !== undefined)
                    me.events[listener] = _config['listeners'][listener];
            }
        }

        _logger = fcs.logManager.getLogger();
    };

    /**
     * @method retrieveCountryCode
     * Retrieves county code based on Device
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     */
    me.retrieveCountryCode = function (success, failure) {
        var encodedAccessCode = encodeURIComponent(me._domainAccessToken);

        $.ajax({
            type: 'GET',
            url: _config.kandyApiUrl + '/domains/countrycodes?key=' + encodedAccessCode,
            crossDomain: true,
            success: function (response) {
                if (response.status === responseCodes.OK) {
                    if (success) {
                        success(response.result);
                    }
                } else if (failure) {
                    failure(response.message, response.status);
                }
            },
            error: function (x, e) {
                _logger.error('ERROR RETRIEVING COUNTRY CODE');

                if (failure) {
                    failure(x.statusText, responseCodes.ajaxError);
                }
            }
        });
    };

    /**
     * @method sendValidationCode
     * Send validation code to phone
     * @param {String} phoneNumber Phone number to send validation SMS to.
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     */
    me.sendValidationCode = function (phoneNumber, countryCode, success, failure) {

        $.ajaxPrefilter("json script", function (options) {
            options.crossDomain = true;
        });

        $.ajax({
            type: 'POST',
            url: _config.kandyApiUrl + '/domains/verifications/smss?key=' + encodeURIComponent(me._domainAccessToken),
            crossDomain: true,
            contentType: 'application/json',
            data: JSON.stringify({
                user_phone_number: phoneNumber,
                user_country_code: countryCode
            }),
            success: function (response) {
                if (response.status === responseCodes.OK) {
                    if (success)
                        success();
                } else if (failure) {
                    failure(response);
                }
            },
            error: function (x, e) {
                if (failure)
                    failure(x.statusText, responseCodes.ajaxError);
            }
        });
    };

    /**
     * @method validateCode
     * Validate SMS code sent to phone
     * @param {String} validationCode Validation code sent to phone.
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     */
    me.validateCode = function (validationCode, success, failure) {
        var encodedAccessCode = encodeURIComponent(me._domainAccessToken);

        $.ajax({
            type: 'GET',
            url: _config.kandyApiUrl + '/domains/verifications/codes?key=' + encodedAccessCode + '&validation_code=' + validationCode,
            crossDomain: true,
            success: function (response) {
                if (response.status === responseCodes.OK) {
                    if (success) {
                        success(response.result.valid, response.result.message);
                    }
                } else if (failure) {
                    failure(response.message, response.status);
                }
            },
            error: function (x, e) {
                if (failure) {
                    failure(x.statusText, responseCodes.ajaxError);
                }
            }
        });
    };

    /**
     * @method register a device
     * Registers a device in Kandy
     * @param {Object}
     * e.g. {
     *        {String} domainAccessToken: "7b81d8e63f5b478382b4e23127260090", // optional
     *        {String} userPhoneNumber: "4034932232",
     *        {String} userCountryCode "UA",
     *        {String} validationCode "1234",
     *        {String} deviceNativeId "3456",
     *        {String} deviceFamily "iPhone",  // optional
     *        {String} deviceName "myPhone",  // optional
     *        {String} clientSwVersion "4",  // optional
     *        {String} deviceOsVersion "801",  // optional
     *        {String} userPassword "pwdxyz13!",  // optional
     *        {Function} success = function() { doSomething(); }
     *        {Function} failure = function() { doSomethingElse(); }
     *   }
     * @return {Object} response object
     * e.g. { user_id: "972542405850",
              full_user_id: "972542405850@domain.com",
     domain_name:  "domain.com",
     user_access_token: "4d405f6dfd9842a981a90daaf0da08fa",
     device_id: "4d405f6dfd9842a389d5b45d65a9dfd0"
     }
     */
    me.register = function (params, success, failure) {

        $.ajaxPrefilter("json script", function (options) {
            options.crossDomain = true;
        });

        $.ajax({
            type: 'POST',
            url: _config.kandyApiUrl + '/api_wrappers/registrations?key=' + encodeURIComponent(me._domainAccessToken),
            crossDomain: true,
            contentType: 'application/json',
            data: JSON.stringify({
                user_phone_number: params.userPhoneNumber,
                user_country_code: params.userCountryCode,
                validation_code: params.validationCode,
                device_native_id: params.deviceNativeId
            }),
            success: function (response) {
                if (response.status === responseCodes.OK) {
                    if (success)
                        success(response);
                } else if (failure) {
                    failure(response.message, response.status);
                }
            },
            error: function (x, e) {
                if (failure)
                    failure(x.statusText, responseCodes.ajaxError);
            }
        });
    };

    /**
     * @method getConfiguration
     * Retrieves domain name, access token, and SPiDR configuration
     * @param {String} domainApiKey
     * @param {String} domainApiSecret
     * @param {Function} success The success callback.
     * @param {Function} failure The failure callback.
     * @return {Object} response object
     * e.g. {
         "domain_name": "domain.com",
         "domain_access_token": "4d405f6dfd9842a981a90daaf0da08fa",
         "spidr_configuration":
             {
                 "REST_server_address":"kandysimplex.fring.com",
                 "REST_server_port":443,
                 "webSocket_server_address":"kandysimplex.fring.com",
                 "webSocket_server_port":8582,
                 "ICE_server_address":"54.84.226.174",
                 "ICE_server_port":3478,
                 "subscription_expire_time_seconds":null,
                 "REST_protocol":"https",
                 "server_certificate":null,
                 "use_DTLS":false,
                 "audit_enable":true,
                 "audit_packet_frequency":null
             }
         }
     */

    me.getConfiguration = function (params, success, failure) {

        var paramStr = 'key=' + encodeURIComponent(params.domainApiKey) +
            '&domain_api_secret=' + encodeURIComponent(params.domainApiSecret);
        $.ajax({
            type: 'GET',
            url: _config.kandyApiUrl + '/api_wrappers/configurations?' + paramStr,
            crossDomain: true,
            success: function (response) {
                if (response.status === responseCodes.OK) {
                    if (success)
                        success({
                            "domainName": response.result.domain_name,
                            "domainAccessToken": response.result.domain_access_token,
                            "spidrConfiguration": {
                                "restUrl": response.result.spidr_configuration.REST_server_address,
                                "restPort": response.result.spidr_configuration.REST_server_port,
                                "protocol": response.result.spidr_configuration.REST_protocol,
                                "websocketIP": response.result.spidr_configuration.webSocket_server_address,
                                "websocketPort": response.result.spidr_configuration.webSocket_server_port,
                                "spidr_env": {
                                    "iceserver": ('stun:' + response.result.spidr_configuration.ICE_server_address + ":" +
                                        response.result.spidr_configuration.ICE_server_port),
                                    "ice": ('STUN stun:' + response.result.spidr_configuration.ICE_server_address + ":" +
                                        response.result.spidr_configuration.ICE_server_port)

                                }
                            }
                        });
                } else if (failure) {
                    failure(response.message, response.status);
                }
            },
            error: function (x, e) {
                if (failure)
                    failure(x.statusText, responseCodes.ajaxError);
            }
        });
    };

    return me;
}());
