<style>
    #myModal .lead-followup-view {
        font-size: 0.92rem;
    }

    #myModal .lead-followup-view .view-label {
        color: #64748b;
        font-size: 0.8rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    #myModal .lead-followup-view .view-value {
        color: #0f172a;
        min-height: 38px;
        padding: 0.55rem 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.55rem;
        background: #f8fafc;
    }

    #myModal .lead-followup-view .view-remark {
        min-height: 96px;
        white-space: pre-wrap;
    }

    #myModal .lead-followup-view .followup-photo-card {
        width: 136px;
        border: 1px solid #e2e8f0;
        border-radius: 0.7rem;
        background: #fff;
        overflow: hidden;
    }

    #myModal .lead-followup-view .followup-photo-card img {
        height: 92px;
        width: 100%;
        object-fit: contain;
        background: #fff;
    }
</style>

<div class="modal-header">
    <h5 class="modal-title">Follow-up Details</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
</div>

<div class="modal-body lead-followup-view">
    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="view-label">Follow-up Date</div>
            <div class="view-value">
                {{ $follow->next_follow_up_date ? $follow->next_follow_up_date->timezone(company()->timezone)->format(company()->date_format) : '--' }}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="view-label">Time</div>
            <div class="view-value">
                {{ $follow->next_follow_up_date ? $follow->next_follow_up_date->timezone(company()->timezone)->format(company()->time_format) : '--' }}
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="view-label">Status</div>
            <div class="view-value">{{ ucfirst((string) ($follow->status ?: 'pending')) }}</div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="view-label">Added By</div>
            <div class="view-value">{{ optional($follow->addedBy)->name ?: 'System' }}</div>
        </div>
        <div class="col-12 mb-3">
            <div class="view-label">Remark</div>
            <div class="view-value view-remark">{{ trim(strip_tags((string) $follow->remark)) ?: '--' }}</div>
        </div>

        @if(isset($followAttachments) && $followAttachments->count())
            <div class="col-12">
                <div class="view-label">Photos</div>
                <div class="d-flex flex-wrap mt-2">
                    @foreach($followAttachments as $attachment)
                        <a href="{{ $attachment->file_url }}" target="_blank" class="followup-photo-card mr-2 mb-2 p-2 text-center text-dark-grey">
                            <img src="{{ $attachment->file_url }}" alt="{{ $attachment->filename }}" class="img-fluid rounded">
                            <small class="d-block mt-2">{{ $attachment->filename }}</small>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<div class="modal-footer">
    <x-forms.button-cancel data-dismiss="modal" class="border-0">@lang('app.close')</x-forms.button-cancel>
</div>
