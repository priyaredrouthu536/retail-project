jQuery(document).ready(function ($) {

    const imgExt = ['jpg','jpeg','png','gif','webp','svg','bmp','tiff'];
    const modelExt = ['obj','stl','fbx','gltf','glb','ply','3ds','dae'];

    function ensureGroup(folder) {
        let group = $('.pk-fastkey-group[data-folder="' + folder + '"]');

        if (group.length) return group;

        $("#pk-fastkey-groups").append(`
            <h3>${folder} (0)</h3>
            <div class="pk-fastkey-group" 
                 data-folder="${folder}"
                 style="display:flex;flex-wrap:wrap;gap:15px;margin-bottom:20px;">
            </div>
        `);

        return $('.pk-fastkey-group[data-folder="' + folder + '"]');
    }

    function refreshCounts() {
        $('.pk-fastkey-group').each(function(){
            let count = $(this).find('.pk-fastkey-item').length;
            $(this).prev('h3').text($(this).data('folder') + ' (' + count + ')');
        });
    }

    let frame;

    $('#pk-upload-fastkey').on('click', function(e){
        e.preventDefault();

        let folder = $('#pk-fastkey-folder').val();
        if (!folder) {
            alert("Please select Image Type");
            return;
        }

        if (frame) return frame.open();

        frame = wp.media({
            title: 'Select FastKey Images',
            button: { text: 'Use these images' },
            library: { type: 'image' },
            multiple: true
        });

        frame.on('select', function(){
            let selection = frame.state().get('selection').toArray();
            let folder = $('#pk-fastkey-folder').val();

            let group = ensureGroup(folder);

            selection.forEach(att => {
                const attrs = att.attributes || {};
                const url  = attrs.url || attrs.sizes?.full?.url || '';
                const name = attrs.filename || attrs.name || attrs.title || '';
                const id   = attrs.id ? parseInt(attrs.id, 10) : 0;
                const ext  = name.split('.').pop().toLowerCase();

                let icon = '';
                if (imgExt.includes(ext)) {
                    icon = `<img src="${url}" style="width:100%;height:90px;object-fit:cover;border-radius:4px;">`;
                } else if (modelExt.includes(ext)) {
                    icon = '<div style="font-size:40px;height:90px;display:flex;align-items:center;justify-content:center;">📦</div>';
                } else {
                    icon = '<div style="font-size:40px;height:90px;display:flex;align-items:center;justify-content:center;">❓</div>';
                }

                group.append(`
                    <div class="pk-fastkey-item"
                        data-name="${name}"
                        data-url="${url}"
                        data-id="${id}"
                        style="width:140px;text-align:center;border:1px solid #ddd;padding:10px;border-radius:6px;background:#fff;">
                        ${icon}
                        <div style="font-size:12px;margin-top:6px;">${name}</div>
                    </div>
                `);
            });
            refreshCounts();
        });

        frame.open();
    });

    $('#pk-fastkey-groups').on('click', '.pk-remove-item', function(e){
        e.preventDefault();
        let group = $(this).closest('.pk-fastkey-group');
        $(this).closest('.pk-fastkey-item').remove();

        if (!group.find('.pk-fastkey-item').length) {
            group.prev('h3').remove();
            group.remove();
        }
        refreshCounts();
    });

    $('#pk-clear-fastkey').on('click', function(){
        $('#pk-fastkey-groups').html('<p>No images uploaded yet.</p>');
    });

    $('#pk-fastkey-form').on('submit', function(e){
        e.preventDefault();

        let items = [];
        let folder = $('#pk-fastkey-folder').val();

        if (!folder) {
            $('#pk-save-msg').html('<span style="color:red;">Please select an Image Type</span>');
            return;
        }

        $('.pk-fastkey-group').each(function(){
            let groupFolder = $(this).data('folder');
            if (groupFolder !== folder) return;

            $(this).find('.pk-fastkey-item').each(function(){
                const fname = $(this).attr('data-name');

                items.push({
                    name: fname,
                    id: $(this).attr('data-id'),
                    url: $(this).attr('data-url'),
                    ext: fname.split('.').pop().toLowerCase()
                });
            });
        });
        if (items.length === 0) {
            $('#pk-save-msg').html('<span style="color:red;">No images found in this category. Please select at least one image.</span>');
            return;
        }
        $('#pk-save-msg').text('Saving...');
        $.post(PinakaFastKey.ajax_url, {
            action: 'pinaka_manage_fastkey_images',
            mode: 'save',
            save_nonce: PinakaFastKey.save_nonce,
            items: items,
            image_type: folder
        }, function(res){
            if (res.success) {
                $('#pk-save-msg').html('<span style="color:green;">'+res.data.message+'</span>');
                setTimeout(() => location.reload(), 600);
            } else {
                $('#pk-save-msg').html('<span style="color:red;">'+res.data.message+'</span>');
            }
        });
    });
    $('#pk-import-fastkey').on('click', function(){
        $('#pk-import-msg').text('Importing...');
        $.post(PinakaFastKey.ajax_url, {
            action: 'pinaka_manage_fastkey_images',
            mode: 'import',
            security: PinakaFastKey.import_nonce
        }, function(res){
            if (res.success) {
                $('#pk-import-msg').html('<span style="color:green;">'+res.data.message+'</span>');
                setTimeout(() => location.reload(), 600);
            } else {
                $('#pk-import-msg').html('<span style="color:red;">'+res.data.message+'</span>');
            }
        });
    });
    $('#pk-fastkey-groups').on('click', '.pk-toggle-status', function(e){
        e.preventDefault();
        let btn  = $(this);
        let id   = btn.data('id');
        let ext  = btn.data('ext');
        let newStatus = btn.data('status');

        $.post(PinakaFastKey.ajax_url, {
            action: 'pinaka_toggle_fastkey_status',
            security: PinakaFastKey.save_nonce,
            id: id,
            ext: ext,
            new_status: newStatus
        }, function(res){
            alert(res.data.message);
            setTimeout(() => location.reload(), 600);
        });

    });
});
