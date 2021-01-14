// For admin media Library
jQuery(document).ready(function() {
    media_sidebar_html();
    media_folder_list();
});

// For upload media modal
// jQuery(document).on('click', '.components-placeholder__fieldset .components-button.is-secondary', function(){
jQuery(document).on('click', '.components-placeholder__fieldset .components-form-file-upload + .components-button', function(){
    media_sidebar_html();
    media_folder_list();

});

// For feature image modal
jQuery(document).on('click', '.components-button.editor-post-featured-image__toggle', function(){
    media_sidebar_html();
    media_folder_list();
});

// When create new folder
jQuery(document).on('click', '.sidebar-btn-new-folder', function(){

    if ( jQuery('#folder-list li input').hasClass( "current-rename-folder" ) || jQuery('#folder-list li').hasClass( "folder-create" ) ) {
        media_notification_message();
        return false;
    }

    var folder_html = ''
    folder_html += '<li class="folder-create">';
        folder_html += '<input class="folder-name" value="New Folder" type="text">';
        folder_html += '<div class="folder-btn-action">';
            folder_html += '<button class="fld-btn-cancel">Cancel</button>';
            folder_html += '<button class="fld-btn-save btn-on-save">Save</button>';
        folder_html += '</div>';
    folder_html += '</li>';

    jQuery('.media-frame-menu .sidebar-folder-list ul#folder-list').prepend(folder_html);

});


// When Click on cancel button
jQuery(document).on('click', '.fld-btn-cancel', function(){
    media_folder_list();
});

// For folder save
jQuery(document).on('click', '.fld-btn-save.btn-on-save', function(){

    var folder_id = jQuery(this).parent().parent().attr('data-id');
    var folder_name = jQuery(this).parent().parent().find('input').val();
    var temp_img = jQuery(this);

    if ( jQuery(this).parent().parent().hasClass( "action_create" ) ) {

        // Folder save when rename
        var data = {
          action: 'media_edit_folder',
          folder_id: folder_id,
          name: folder_name,
          parent: 0,
        };
        jQuery.post(epa_media_ajax_object.ajax_url, data, function(response_str) {
            var response = JSON.parse(response_str);

            if (response.sucess_status == '1') {

                var loading_html = '<img src="'+ epa_media_view_loader.image +'">';
                temp_img.append(loading_html);

                media_folder_list();

            } else {
                alert(response.messege);
                return false;
            }
        });

    } else {

        // Folder save when new folder create
        var data = {
          action: 'media_create_folder',
          name: folder_name,
          // nonce_data : jQuery(this).parent().parent().data( 'nonce' ),
          parent: 0,
        };
        jQuery.post(epa_media_ajax_object.ajax_url, data, function(response_str) {

            var response = JSON.parse(response_str);
            if (response.sucess_status == '1') {
                var loading_html = '<img src="'+ epa_media_view_loader.image +'">';
                temp_img.append(loading_html);

                media_folder_list();

            } else {
                alert(response.messege);
                return false;
            }


        });
    }
});

// Rename folder name
jQuery(document).on('click', '.sidebar-btn-rename', function(){

    if ( jQuery('#folder-list li input').hasClass( "current-rename-folder" ) || jQuery('#folder-list li').hasClass( "folder-create" ) ) {
        media_notification_message();
        return false;
    }

    jQuery('.action-active-folder span').text('');
    var folder_name = jQuery('.action-active-folder').text();

    jQuery('#folder-list input').removeClass("current-rename-folder");

    rename_html = '';
    rename_html += '<input class="folder-name current-rename-folder" value="'+ folder_name +'" type="text">';
    rename_html += '<div class="folder-btn-action">';
        rename_html += '<button class="fld-btn-cancel">Cancel</button>';
        rename_html += '<button class="fld-btn-save btn-on-save">Save</button>';
    rename_html += '</div>';

    jQuery('.action-active-folder').parent().addClass('action_create folder-create').html(rename_html); 
    jQuery('.current-rename-folder').focus();

});

jQuery(document).on('click', '.folder-list-item', function(){

    if ( jQuery('#folder-list li input').hasClass( "current-rename-folder" ) || jQuery('#folder-list li').hasClass( "folder-create" ) ) {
        media_notification_message();
        return false;
    }

    jQuery('.sidebar-btn-rename').attr("disabled", true);
    jQuery('.sidebar-btn-delete').attr("disabled", true);

    jQuery('#folder-list a').removeClass("action-active-folder");
    if ( ! jQuery(this).parent().hasClass( "readable-folder" ) && ( jQuery(this).parent().attr('data-id') != -1 ) && ( jQuery(this).parent().attr('data-id') != 0 ) ) {

        jQuery(this).addClass("action-active-folder");
        jQuery('.sidebar-btn-rename').attr("disabled", false);
        jQuery('.sidebar-btn-delete').attr("disabled", false);
    }

});

// For display folder's files
jQuery(document).on('click', '.media-modal-sidebar .sidebar-folder-list li a', function( e ){
    e.preventDefault();
    jQuery('#folder-list .current-active-folder').removeClass('current-active-folder');
    jQuery(this).addClass('current-active-folder');

    var folder_id = jQuery(this).parent().attr('data-id');

    reload_media_library( folder_id );

});

jQuery(document).on('click', '.sidebar-btn-delete', function(){

    // For only one action perform at a time
    if ( jQuery('#folder-list li input').hasClass( "current-rename-folder" ) || jQuery('#folder-list li').hasClass( "folder-create" ) ) {
        media_notification_message();
        return false;
    }
    
    if ( jQuery('#folder-list a').hasClass( "action-active-folder" ) ) {

        // For delete confirmation
        if(!confirm('Are you sure?')) {
            media_folder_list();
            return false;
        }

        var folder_id = jQuery('.action-active-folder').parent().attr('data-id');
        var data = {
            action: 'media_delete_folder',
            folder_id: folder_id,
        };
        jQuery.post(epa_media_ajax_object.ajax_url, data, function(response_str) {

            var response = JSON.parse(response_str);
            if( response.data != '' ) {

                var notification_message = '';
                notification_message += '<div class="media-view-notification-message"><span class="notification-content"><i class="fa fa-check-circle"></i> Successfully Deleted!</span></div>';

                jQuery('body').append(notification_message);
                setTimeout(function(){ jQuery('.media-view-notification-message').slideUp('fast',function(){
                }); }, 5000);

                media_folder_list();

            }
        });
    }

});

// For Display Folder List
function media_folder_list(){
    jQuery.ajax({
        url: epa_media_ajax_object.ajax_url,
        type: 'GET',
        data: {
            action : 'get_folder_list'
        }
    })
    .done(function (response_str) {

        var response = JSON.parse(response_str);

        if (response.sucess_status == '1') {

            var search_input_value = jQuery('.media-modal-sidebar .search-folder input').val();
            reload_media_library( -1 );

            setTimeout(function(){
                jQuery('.media-frame-menu .sidebar-folder-list').html(response.data);
                jQuery('.sidebar-btn-rename').attr("disabled", true);
                jQuery('.sidebar-btn-delete').attr("disabled", true);

                if( search_input_value != '' ) {
                    media_search_folder(search_input_value);
                }

            },500);


            setTimeout(function(){
                media_files_drag();
            },1500);
        }
    });
}

// For Media Sidebar 
function media_sidebar_html() {

    jQuery('.media-modal-sidebar').remove();
    setTimeout(function(){

        var sidebar_html = '';
        sidebar_html += "<div class='media-modal-sidebar'>";
            sidebar_html += "<div class='media-view-wrapper'>";
                sidebar_html += "<div class='inner-media-view-wrapper'>";
                    sidebar_html += "<div class='media-sidebar-header'>";
                        sidebar_html += "<h2 class='media-sidebar-header-title'>Folders</h2>";
                        sidebar_html += "<button class='sidebar-btn-new-folder'><i class='fa fa-folder-plus'></i><span>New Folder</span></button>";
                    sidebar_html += "</div>";
                    sidebar_html += "<div class='sidebar-folder-action-bar'>";
                        sidebar_html += "<button class='sidebar-btn-rename action-btn'><i class='fa fa-edit'></i><span>Rename</span></button>";
                        sidebar_html += "<button class='sidebar-btn-delete action-btn'><i class='fa fa-trash'></i><span>Delete</span></button>";
                    sidebar_html += "</div>";
                    sidebar_html += "<div class='sidebar-folder-list'>";
                    sidebar_html += "</div>";
                sidebar_html += "</div>";
            sidebar_html += "</div>";
            sidebar_html += "</div>";
        sidebar_html += "</div>";

        jQuery('.media-frame-menu .media-menu').append(sidebar_html);

    }, 300);

}

// For notification message
function media_notification_message() {
    var notification_message = '';
    notification_message += '<div class="media-view-notification-message"><span class="notification-content"><i class="fa fa-exclamation-circle"></i> You are editing another folder! Please complete the task first!</span></div>';

    jQuery('body').append(notification_message);
    setTimeout(function(){ jQuery('.media-view-notification-message').slideUp('fast',function(){}); }, 3000);
    return false;
}

// For Save Drag File
function save_drag_files(folder_id, attachment_id) {

    var data = {
        action: 'save_folder_files',
        folder_id: folder_id,
        attachment_id: attachment_id,
    };
    jQuery.post(epa_media_ajax_object.ajax_url, data, function(response_str) {

        var response = JSON.parse(response_str);
        if (response.sucess_status == '1') {

            var notification_message = '';
            notification_message += '<div class="media-view-notification-message"><span class="notification-content"><i class="fa fa-check-circle"></i> '+ response.messege +'</span></div>';

            jQuery('body').find('.media-view-notification-message').remove();
            jQuery('body').append(notification_message);
            setTimeout(function(){ jQuery('.media-view-notification-message').slideUp('fast',function(){}); }, 5000);

            media_folder_list();
        } else {
            alert(response.messege);
            return false;
        }


    });
}

// For Media Library refresh
function reload_media_library( folder_data ) {

    if ( wp.media && wp.media.frame.content.get() != null && wp.media.frame.content.get() != undefined) {

        if( wp.media.frame.content.get().collection != null && wp.media.frame.content.get().collection != undefined) {
            wp.media.frame.content.get().collection.props.set({folder_data: folder_data, ignore: (+(new Date()))});
            wp.media.frame.content.get().options.selection.reset();
        }
    }

    setTimeout(function() {
        media_files_drag()
    }, 1000);
}


// For files drag and drop
function media_files_drag() {

    jQuery('.attachment.save-ready:not(.undragable-file)').draggable({
        revert: true ,
        containment: 'body',
        helper: 'clone',
        opacity: 0.70,
        zIndex: 999999,
        appendTo: ".media-frame-menu",
        cursor: 'move'  
    });
    jQuery('.attachment.save-ready .attachment-preview.draggable-false').parent().draggable( 'disable' );

    jQuery(".media-frame-menu .sidebar-folder-list ul li.dropable-folder").droppable({
        activeClass:"ui-state-active",
        hoverClass: "rotate",
        accept:".attachment.save-ready:not(.undragable-file)",
        drop: function(event,ui) {

            event.stopPropagation();
            event.preventDefault();

            ui.draggable.fadeOut(function () {
                ui.draggable.remove();
            });

            var folder_id = jQuery(this).attr('data-id');
            var attachment_id = ui.draggable.attr("data-id");

            if( attachment_id != '' && folder_id != '' ) {
                save_drag_files(folder_id , attachment_id);
            }

        }
    }); 
}


// For Search Folder from folder list
jQuery(document).ready(function(){
    jQuery.expr[':'].icontains = function(obj, index, meta, stack){
     return (obj.textContent || obj.innerText || jQuery(obj).text() || '').toLowerCase().indexOf(meta[3].toLowerCase()) >= 0;
    };
});
// For Search Folder from folder list
jQuery(document).on('keyup', '.media-modal-sidebar .search-folder input', function(){
    var input_value = jQuery.trim(jQuery(this).val());
    media_search_folder(input_value);
});

// function for search folder
function media_search_folder(search_input_value = "") {

    if(jQuery.trim(search_input_value) != "")
    {
        jQuery(".media-frame-menu .sidebar-folder-list ul#folder-list li a").removeClass("tempselector");
      
        jQuery(".media-frame-menu .sidebar-folder-list ul#folder-list li a").parent().children(":icontains('"+jQuery.trim(search_input_value)+"')").each(function(){
            jQuery(this).addClass("tempselector");
        });
      
        jQuery(".media-frame-menu .sidebar-folder-list ul#folder-list li a.tempselector").show();
        jQuery(".media-frame-menu .sidebar-folder-list ul#folder-list li a:not('.tempselector')").hide();
      
        jQuery(".media-frame-menu .sidebar-folder-list ul#folder-list li a").removeClass("tempselector");
    }
    else
    {
        jQuery(".media-frame-menu .sidebar-folder-list ul#folder-list li a").show();
    } 
    jQuery('.media-modal-sidebar .search-folder input').val(search_input_value);
}

// For undragable files
jQuery(document).ready(function(){
    if( jQuery('#tmpl-attachment').length > 0 ) {
        jQuery('#tmpl-attachment').html( jQuery('#tmpl-attachment').html().replace('{{ data.orientation }}', '{{ data.orientation }} {{ data.draggable_flag }} ') );
    }
});

