function updateLiveTime() {
    moment.locale('ar');

    moment.updateLocale('ar', {
        postformat: function (string) {
            return string;
        }
    });

    const time = moment().format('hh:mm:ss A');
    const date = moment().format('dddd، D MMMM YYYY');

    $('#clock').text(time);
    $('#date').text(date);
}

$(document).ready(function() {
    updateLiveTime();
    setInterval(updateLiveTime, 1000);
});
