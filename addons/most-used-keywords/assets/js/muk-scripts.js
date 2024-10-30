;(($ , dt) => {
    "use strict";

    $(document).ready(() => {

        var $muk =  $('#most-used-keywords')
        
        if(dt['keywords'].length > 0)
        {
            $muk.empty();
        }

        dt['keywords'].forEach((e,i)=>{

            var $meta = $('<li>')
                .html(e['meta'])
                .attr('data-count' , e['count'])
                .appendTo($muk);

            var $count = $('<i>').text(e['count']).appendTo($meta);
            
        });
    })
    
})(jQuery , HeimdallData);
