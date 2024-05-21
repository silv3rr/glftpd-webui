// submit form on ENTER key, depending on field

(function() {
    //let apply = document.getElementById('form');
    form.addEventListener('keypress', function(event) {
        //console.log('DEBUG: ',  document.activeElement.id)
        if (event.key === "Enter") {
            event.preventDefault();
            if (/^(new_user_name|new_user_password|new_user_ip|new_user_group)$/.test(document.activeElement.id)) {
                document.getElementById('add_user').click();
            }
            if (document.activeElement.id === 'new_group') {
                document.getElementById('add_group').click();
            }
            document.getElementById('apply').click();
        }
    });
}());
