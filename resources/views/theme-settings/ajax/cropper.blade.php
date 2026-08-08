<style>
    #imageCropper {
        height: 350px;
    }
</style>

<div class="modal-header">
    <h5 class="modal-title">@lang('app.cropImage')</h5>
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
</div>
<div class="modal-body">
    <div class="row d-flex align-content-center justify-content-center">
        <img id="imageCropper" src="" alt="Picture">
    </div>
</div>
<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0 mr-3">@lang('app.cancel')</x-forms.button-cancel>
    <x-forms.button-primary id="cropImage" icon="crop">@lang('app.crop')</x-forms.button-primary>
</div>

<script>
    var elementId = '{{ $element }}';
    var img = document.getElementById('imageCropper');
    var cropper;
    var canvas;
    // logo id input file and set to image
    var input = document.getElementById(elementId);
    var files = input.files;

    function dataURLtoFile(dataurl) {

        var arr = dataurl.split(','),
            mime = arr[0].match(/:(.*?);/)[1],
            bstr = atob(arr[1]),
            n = bstr.length,
            u8arr = new Uint8Array(n);

        while(n--){
            u8arr[n] = bstr.charCodeAt(n);
        }

        var extension = mime === 'image/jpeg' ? 'jpg' : 'png';

        return new File([u8arr], Math.random().toString(36).substr(2, 10) + '.' + extension, {
            type: mime,
            lastModified: Date.now()
        });
    }

    if (files.length > 0) {
        var file = files[0];
        var reader = new FileReader();
        reader.onload = function (e) {
            img.src = e.target.result;

            // delay to load image
            setTimeout(function () {
                cropper = new Cropper(img, {
                    viewMode: 1,
                });
            }, 200);

        }
        reader.readAsDataURL(file);
    }

    $('#cropImage').click(function () {
        if (!cropper) {
            return;
        }

        $('#cropImage').attr('disabled', true);
        var isLoginBackground = elementId === 'login_background';
        var canvasOptions = isLoginBackground
            ? { maxWidth: 1600, maxHeight: 900, imageSmoothingQuality: 'high' }
            : { maxWidth: 1000, maxHeight: 500, imageSmoothingQuality: 'high' };
        var outputType = isLoginBackground ? 'image/jpeg' : 'image/png';
        var outputQuality = isLoginBackground ? 0.85 : 1;

        canvas = cropper.getCroppedCanvas(canvasOptions);
        var croppedDataUrl = canvas.toDataURL(outputType, outputQuality);
        var croppedFile = dataURLtoFile(croppedDataUrl);

        if (croppedFile.size > 2 * 1024 * 1024) {
            $('#cropImage').attr('disabled', false);
            toastr.error('The cropped image must be smaller than 2 MB. Please crop a smaller area.');
            return;
        }

        // set the new file to the input file on the element
        let container = new DataTransfer();
        container.items.add(croppedFile);
        input.files = container.files;

        // change dropify image
        $('#' + elementId).parent().find('.dropify-render img').attr('src', croppedDataUrl);

        // close modal
        elementId = '';
        $(MODAL_LG).modal('hide');
    });

    function onModelClose() {
        if(elementId != undefined && elementId != '') {
            $('#' + elementId).parent().find('.dropify-clear').click();
            if (cropper) {
                cropper.destroy();
            }
            elementId = '';
        }
    }

    $(MODAL_LG).on('hidden.bs.modal', function (e) {
        onModelClose();
        $(MODAL_LG + ' .modal-content').html('');
        $(MODAL_LG).off('hidden.bs.modal');
    });
</script>
