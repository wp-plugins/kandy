/**
 * You login success
 */
window.login_success_callback = function () {
    //do something here
};

/**
 * you login fail
 */
window.login_failed_callback = function () {
//do something here
};

/**
 * Someone are calling you
 * @param call
 * @param isAnonymous
 */
window.call_incoming_callback = function (call, isAnonymous) {
    //do something here
};

/**
 * You are on call
 * @param call
 */
window.on_call_callback = function (call) {
    //do something here
};

/**
 * Some one answer your call
 * @param call
 * @param isAnonymous
 */
window.call_answered_callback = function (call, isAnonymous) {
    //do something here
};

/**
 * end call callback
 */
window.call_ended_callback = function () {
    //do something here
};

/**
 * Callback when click AnswerVideo Button
 * @param stage
 */
window.answer_video_call_callback = function (stage) {
    //do something here
};

/**
 * Callback when click AnswerVideo Button
 * @param stage
 */
window.answer_voice_call_callback = function (stage) {
    //do something here
};

/**
 * Callback when click Call Button
 * @param stage
 */
window.make_call_callback = function (stage) {
    //do something here
};

/**
 * Callback when click End call Button
 * @param stage
 */
window.end_call_callback = function (stage) {
    //do something here
};

/**
 * remote video callback
 * @param videoTag
 */
window.remote_video_initialized_callback = function(videoTag){
    //do something here
};

/**
 * Your local video callback
 * @param videoTag
 */
window.local_video_initialized_callback = function(videoTag){
    //do something here
};
