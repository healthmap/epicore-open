/**
 * Created by jeffandre on 3/16/17.
 */
//document.addEventListener("deviceready",onDeviceReady,false);
function onDeviceReady(){
    var push = PushNotification.init({
        android: {senderID: "808458117906"},
        browser: {
            pushServiceURL: 'http://push.api.phonegap.com/v1/push'
        },
        ios: {
            alert: "true",
            badge: "true",
            sound: "true",
            clearBadge: "true"  // clears badge on app startups
        },
        windows: {}
    });
    push.on('registration', function(data) {
        //console.log(data.registrationId);
        //document.getElementById("gcm_id").innerHTML = data.registrationId;
        //alert("ID: " +data.registrationId);
    });

    push.on('notification', function(data) {
        //alert("Message: " +data.message);

    });

    push.on('error', function(e) {
        alert(e);
    });

    /*push.setApplicationIconBadgeNumber(function() {
        //console.log('success');
    }, function() {
        console.log('set badge number error');
    }, 0); // badge number.  set to zero to clear it.*/
}
