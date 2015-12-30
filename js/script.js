$(document).ajaxError(function(event, xhr, settings) {
    if(xhr.status == 401) {
        location.reload();
    }
});
