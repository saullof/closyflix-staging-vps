<div class="modal fade" tabindex="-1" role="dialog" id="broadcaster-camera-settings-dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{__("Camera settings")}}</span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{{__('Close')}}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Device selection controls -->
                <div id="device-controls">
                    <div class="form-group">
                        <label for="cameraSelect">{{__("Camera")}}</label>
                        <select id="cameraSelect" class="form-control"></select>
                    </div>
                    <div class="form-group">
                        <label for="microphoneSelect">{{__("Microphone")}}</label>
                        <select id="microphoneSelect" class="form-control"></select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary stream-save-btn" onclick="Broadcast.saveCameraSettings();">{{__('Save')}}</button>
            </div>
        </div>
    </div>
</div>
