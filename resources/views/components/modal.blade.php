<div class="modal fade" id="{{$id}}" tabindex="-1" aria-labelledby="{{$id}}Label" aria-hidden="true">
    <div class="modal-dialog {{$size}}">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">{{$heading}}</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{$slot}}
            </div>
        </div>
    </div>
</div>