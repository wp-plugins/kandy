/**
 * Created by Khanh on 22/6/2015.
 */
(function(){
    var openSessions = [];
    var currentSession;
    var myOwnSessions = [];// sessions that current user created
    var mySessions    = [];// sessions that current user is a participant
    var browsingType;
    var sessionListeners = {
        'onUserJoinRequest': kandy_onSessionJoinRequest,
        'onJoinApprove': kandy_onSessionJoinApprove
    };
    var cobrowsing = window.cobrowsing || {};
    var currentKandyUser = cobrowsing.current_user.user_id + '@' + cobrowsing.current_user.domain_name;
    var btnTerminate = jQuery("#coBrowsing .buttons #" + cobrowsing.btn_terminate_id);
    var btnStartCoBrowsing = jQuery("#coBrowsing .buttons #"+ cobrowsing.btn_start_cobrowsing_id);
    var btnConnect = jQuery("#coBrowsing .buttons #"+ cobrowsing.btn_connect_session_id);
    var btnStartBrowsingViewer = jQuery("#coBrowsing .buttons #"+cobrowsing.btn_start_browsing_viewer_id);
    var btnLeave = jQuery("#coBrowsing .buttons #"+cobrowsing.btn_leave_id);
    var slSessionList = jQuery('#'+cobrowsing.session_list_id);
    var btnStop = jQuery('#'+cobrowsing.btn_stop_id);
    function displayButtons(){
        var isAdmin = false, isMember = false;
        currentSession = openSessions[parseInt(slSessionList.val())];
        if(typeof  currentSession != 'undefined'){
            isAdmin  = myOwnSessions.indexOf(currentSession.session_id) > -1;
            isMember = (mySessions.indexOf(currentSession.session_id) > -1 && !isAdmin);
        }

        //if current user is owner of this session
        if(isAdmin){
            btnTerminate.show();
            btnStartCoBrowsing.show();
            btnConnect.hide();
            btnStartBrowsingViewer.hide();
            btnLeave.hide();
        }else{
            if(isMember){
                btnStartBrowsingViewer.show();
                btnConnect.hide();
                btnStartCoBrowsing.hide();
                btnTerminate.hide();
                btnLeave.show();
            }else {
                btnConnect.show();
                btnStartCoBrowsing.hide();
                btnStartBrowsingViewer.hide();
                btnTerminate.hide();
                btnLeave.hide();
            }
        }
    }

    window.loadSessionList = function(sessions) {
        var i = 0;
        var sessionList = slSessionList;
        sessionList.empty();
        openSessions = [];
        if(sessions.length){
            sessions.forEach(function(session){
                //only use session with type = cobrowsing
                if(session.session_type == 'cobrowsing'){
                    sessionNames[session.session_id] = session.session_name;
                    openSessions.push(session);
                    if((session.admin_full_user_id == currentKandyUser) && (myOwnSessions.indexOf(session.session_id) == -1)){
                        myOwnSessions.push(session.session_id);
                    }
                    kandy_getSessionInfo(session.session_id,function(result){
                        result.session.participants.forEach(function(p){
                            if((p.full_user_id == currentKandyUser) && (mySessions.indexOf(session.session_id) == -1)){
                                mySessions.push(session.session_id);
                            }
                        })
                    });
                    KandyAPI.Session.setListeners(session.session_id,sessionListeners);
                    var option = jQuery("<option>").val(i).text(session.session_name || session.session_id);
                    sessionList.append(option);
                    i++;
                }
            });
            setTimeout(displayButtons,3000);
        }

    };
    window.sessionJoinApprovedCallback = function(sessionId) {
        mySessions.push(sessionId);
        displayButtons();
    };
    /* Document ready */
    jQuery(function(){
        jQuery("#kandy-chat-create-group-modal").dialog({
            autoOpen: false,
            height: 300,
            width: 600,
            modal: true,
            buttons: {
                "Save": function() {
                    var groupName = jQuery('#kandy-chat-create-session-name').val();
                    var creationTime = new Date().getTime();
                    var timeExpire = creationTime + 31536000;// expire in 1 year
                    if(groupName == ''){
                        alert('Session must have a name.');
                        jQuery('#kandy-chat-create-session-name').focus();
                    } else {
                        var config = { //config
                            session_type: 'cobrowsing',
                            session_name: groupName,
                            creation_timestamp: creationTime,
                            expiry_timestamp: timeExpire
                        };
                        kandy_createSession(config, function(){
                            kandy_getOpenSessionsByType('cobrowsing', loadSessionList);
                        });
                        jQuery('#kandy-chat-create-session-name').val('');
                        jQuery( this ).dialog( "close" );
                    }
                },
                Cancel: function() {
                    jQuery( this ).dialog( "close" );
                }
            }
        });

        jQuery("#btnCreateSession").click(function(){
            jQuery("#kandy-chat-create-group-modal").dialog('open');
            jQuery('#kandy-chat-create-session-name').focus();
        });

        btnConnect.click(function(){
            currentSession = openSessions[parseInt(slSessionList.val())];
            kandy_joinSession(currentSession.session_id);
        });
        slSessionList.on('change',displayButtons);

        btnTerminate.on('click', function(){
            var confirm = window.confirm("Are you sure to terminate this session?")
            if(confirm){
                var session = openSessions[parseInt(slSessionList.val())];
                myOwnSessions.splice(myOwnSessions.indexOf(session.session_id,1));
                mySessions.splice(mySessions.indexOf(session.session_id),1);
                kandy_terminateSession(session.session_id, getCoBrowsingSessions);
            }
        });
        btnStartCoBrowsing.on('click', function(){
            if(currentSession){
                jQuery("#coBrowsing").addClass("browsing");
                slSessionList.attr("disabled", true);
                browsingType = 'user';
                kandy_startCoBrowsing(currentSession.session_id);
            }
        });
        btnStartBrowsingViewer.on('click', function(){
            if(currentSession){
                browsingType = 'agent';
                slSessionList.attr("disabled", true);
                jQuery("#coBrowsing").addClass("browsing");
                kandy_startCoBrowsingAgent(currentSession.session_id, document.getElementById(cobrowsing.holder_id));
            }
        });

        btnStop.on('click', function(){
            jQuery("#coBrowsing").removeClass("browsing");
            try{
                if(browsingType == 'user'){
                    kandy_stopCoBrowsing();
                }else if(browsingType == 'agent'){
                    kandy_stopCoBrowsingAgent();
                }
            }catch(e){
                console.log("Error:");
                console.log(e);
            }finally {
                slSessionList.attr("disabled", false);
            }
        });
        btnLeave.on('click', function(){
            var confirm = window.confirm("Are you sure to leave this session?");
            if(confirm){
                if(currentSession){
                    //delete from my session array
                    kandy_LeaveSession(currentSession.session_id, function(){
                        mySessions.splice(mySessions.indexOf(currentSession),1);
                        displayButtons();
                    });
                }
            }
        })
    });
})();
