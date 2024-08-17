// show modal using function

function ttyModal() {   
    setTimeout(function(){
        $('#bsModalLabel').text('Terminal - by GoTTY');
        $('#bsModalFrame').attr("src", '/tty/');
    }, 2500);
    $('iframe').hide();
    $('#bsModalLabel').text('Loading...')
    $('.modal-body p').html('<div class="spinner-border text-secondary" role="status">');
    $('.modal-body p').append('&nbsp;&nbsp;Terminal is being started, please wait...<br/><br/>');
    $('#bsModal').modal('show');
    setTimeout(function(){
        $('.out').hide();
        $('.modal-body p').hide();
        $('iframe').attr('src', '/tty/');
        $('iframe').show();
    }, 2500);
    $(document).keydown(function(event) { 
        if (event.keyCode == 27) { 
            $('.modal-backdrop').hide();
            $('#bsModal').hide();
        }
    }); 
}

function showModal(title, html) {
    setTimeout(function(){
        let parsed_html = $.parseHTML(html);
        $('iframe').hide();
        $('#bsModalLabel').text(title);
        $('.modal-body p').show();
        $('.modal-body p').attr("style", "background-color:black");
        $('.modal-body p').html($(parsed_html).text());
        //$('.modal-body p').html(parsed_html);
    }, 100);
    $('#bsModal').modal('show');
    $(document).keydown(function(event) { 
        if (event.keyCode == 27) { 
            $('.modal-backdrop').hide();
            $('#bsModal').hide();
        }
    });
}
