/**
 * Created by Khanh on 28/5/2015.
 */

var LiveChatUI = {};
var checkAvailable;
LiveChatUI.changeState = function(state){
    switch (state){
        case 'WAITING':
            jQuery('.liveChat #waiting').show();
            jQuery(".liveChat #registerForm").hide();
            jQuery(".liveChat .customerService ,.liveChat #messageBox, .liveChat .formChat").hide();
            break;
        case 'READY':
            jQuery(".liveChat #registerForm").hide();
            jQuery('.liveChat #waiting').hide();
            jQuery(".liveChat .customerService, .liveChat #messageBox, .liveChat .formChat").show();
            jQuery('.liveChat .agentName').html(agent.username);
            jQuery(".liveChat #messageBox li.their-message span.username").html(agent.username);
            jQuery(".liveChat .handle.closeChat").show();
            break;
        case "UNAVAILABLE":
            jQuery(".liveChat #waiting p").html('There is something wrong, please try again later.');
            jQuery(".liveChat #loading").hide();
            break;
        case "RECONNECTING":
            jQuery(".liveChat #waiting p").html('Chat agents not available, please wait...');
            jQuery(".liveChat #loading").show();
            break;
        case "RATING":
            jQuery(".liveChat #ratingForm").show();
            jQuery(".liveChat .customerService, .liveChat #messageBox, .liveChat .formChat").hide();
            break;
        case "ENDING_CHAT":
            jQuery(".liveChat #ratingForm form").hide();
            jQuery(".liveChat #ratingForm .formTitle").hide();
            jQuery(".liveChat #ratingForm .message").show();
            break;
        default :
            jQuery('.liveChat #registerForm').show();
            jQuery(".liveChat .customerService, .liveChat #messageBox, .liveChat .formChat").hide();
            break;
    }
};

var login = function(domainApiKey, userName, password, success_callback) {
    kandy.login(domainApiKey, userName, password, success_callback);
};

var logout = function(){
    kandy.logout();
};
var login_success_callback = function (){
    console.log('login successful')
    LiveChatUI.changeState("READY");
};
var login_fail_callback = function (){
    console.log('login failed')
    LiveChatUI.changeState("UNAVAILABLE");
};

var getKandyUsers = function(){
    jQuery.ajax({
        url: ajax_object.ajax_url + '?action=kandy_get_free_user',
        type: 'GET',
        async: false,
        dataType: 'json',
        success: function(res){
            if(checkAvailable){
                LiveChatUI.changeState('RECONNECTING');
            }else{
                LiveChatUI.changeState('WAITING');
            }
            if(res.status == 'success'){
                if(checkAvailable){
                    clearInterval(checkAvailable);
                }
                var username = res.user.full_user_id.split('@')[0];
                login(res.apiKey, username, res.user.password, login_success_callback, login_fail_callback);
                agent = res.agent;
                setInterval(getIm, 3000);
            }else{
                if(!checkAvailable){
                    checkAvailable = setInterval(getKandyUsers, 5000);
                }
            }
        },
        error: function(){
            LiveChatUI.changeState("UNAVAILABLE");
        }
    })
};

var endChatSession = function(){
    logout();
    jQuery.ajax({
        url: ajax_object.ajax_url + '?action=kandy_end_chat_session',
        type: 'GET',
        async: false,
        success: function(){
            window.onbeforeunload = null;
        }
    });
};

var sendIM = function(username, message){
    KandyAPI.Phone.sendIm(username, message, function () {
            var messageBox = jQuery("#messageBox");
            messageBox.find("ul").append("<li class='my-message'><span class='username'>Me: </span>"+jQuery("#messageToSend").val()+"</li>");
            jQuery("#formChat")[0].reset();
            messageBox.scrollTop(messageBox[0].scrollHeight);
        },
        function () {
            alert("IM send failed");
        }
    );
};

var getIm = function(){
    KandyAPI.Phone.getIm(
        //success callback
        function(data){
            if(data.messages.length){
                for(var i = 0; i< data.messages.length; i++){
                    var msg = data.messages[i];
                    if(msg.messageType == 'chat'){
                        var sender = agent.username;
                        var message = msg.message.text;
                        var messageBox = jQuery("#messageBox");
                        messageBox.find("ul").append("<li class='their-message'><span class='username'>"+sender+": </span>"+message+"</li>");
                        messageBox.scrollTop(messageBox[0].scrollHeight);
                    }
                }
            }
        },
        //fail callback
        function(){}
    )
};

jQuery(function(){
    //hide vs restore box chat
    jQuery(".handle.minimize, #restoreBtn").click(function(){
        jQuery(".liveChat").toggleClass('kandy_hidden');
    });

    jQuery(".handle.closeChat").click(function(){
        LiveChatUI.changeState('RATING');
    });

    jQuery("#customerInfo").on('submit', function(e){
        var form = jQuery(this);
        e.preventDefault();
        jQuery.ajax({
            url: ajax_object.ajax_url + '?action=kandy_register_guest',
            data: form.serialize(),
            type: 'POST',
            success: function(res){
                res = jQuery.parseJSON(res);
                if(res.hasOwnProperty('errors')){
                    form.find("span.error").empty().hide();
                    for(var e in res.errors){
                        form.find('span[data-input="'+e+'"]').html(res.errors[e]).show();
                    }
                }else{
                    LiveChatUI.changeState('WAITING');
                    getKandyUsers();
                }
            }
        })
    });

    //form chat submit handle
    jQuery("#formChat").on('submit', function(e){
        e.preventDefault();
        sendIM(agent.full_user_id, jQuery("#messageToSend").val());
    });
    //end chat session if user close browser or tab
    window.onbeforeunload = function() {
        endChatSession();
    };
    /** Rating for agents JS code **/
    jQuery(".liveChat #ratingForm #btnEndSession").click(function(e){
        e.preventDefault();
        LiveChatUI.changeState('ENDING_CHAT');
        setTimeout(endChatSession, 3000);
        window.location.reload();
    });
    jQuery('.liveChat #ratingForm #btnSendRate').click(function(e){
        e.preventDefault();
        rateData = rateData || {};
        var rateComment = jQuery(".liveChat #rateComment").val();
        if(rateComment){
            rateData.comment = rateComment
        }
        jQuery.ajax({
            url: ajax_object.ajax_url + "?action=kandy_rate_agent",
            data: rateData,
            type: 'POST',
            dataType: 'json',
            success: function (res){
                if(res.success){
                    LiveChatUI.changeState("ENDING_CHAT");
                    setTimeout(endChatSession, 3000);
                    window.location.reload();
                }
            }
        })
    })
});
