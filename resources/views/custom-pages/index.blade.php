@extends('layouts.app')

@section('content')
    <div class="row">
        <div class="col-12">
            <x-cards.data :title="__($pageTitle)">
                <x-slot name="action">
                    <x-forms.button-primary icon="plus" id="addCustomPage">
                        Add Custom Page
                    </x-forms.button-primary>
                </x-slot>

                <div class="table-responsive">
                    <x-table class="table-bordered">
                        <x-slot name="thead">
                            <th>#</th>
                            <th>Title</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Public URL</th>
                            <th class="text-right">Action</th>
                        </x-slot>

                        @forelse ($customPages as $key => $customPage)
                            <tr class="row{{ $customPage->id }}">
                                <td>{{ $key + 1 }}</td>
                                <td>{{ $customPage->page_title }}</td>
                                <td>{{ $customPage->slug }}</td>
                                <td>
                                    <span class="badge badge-{{ $customPage->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($customPage->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('custom-pages.public', $customPage->slug) }}" target="_blank">
                                        {{ route('custom-pages.public', $customPage->slug) }}
                                    </a>
                                </td>
                                <td class="text-right">
                                    <x-forms.button-secondary class="mr-2 edit-custom-page" icon="edit"
                                        data-custom-page-id="{{ $customPage->id }}">
                                        Edit
                                    </x-forms.button-secondary>
                                    <x-forms.button-secondary class="delete-custom-page" icon="trash"
                                        data-custom-page-id="{{ $customPage->id }}">
                                        Delete
                                    </x-forms.button-secondary>
                                </td>
                            </tr>
                        @empty
                            <x-cards.no-record-found-list />
                        @endforelse
                    </x-table>
                </div>
            </x-cards.data>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $('#addCustomPage').click(function () {
            $.ajaxModal(MODAL_LG, "{{ route('custom-pages.create') }}");
        });

        $('body').on('click', '.edit-custom-page', function () {
            let url = "{{ route('custom-pages.edit', ':id') }}";
            url = url.replace(':id', $(this).data('custom-page-id'));
            $.ajaxModal(MODAL_LG, url);
        });

        $('body').on('click', '.delete-custom-page', function () {
            const id = $(this).data('custom-page-id');
            Swal.fire({
                title: "@lang('messages.sweetAlertTitle')",
                text: "@lang('messages.recoverRecord')",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: "@lang('messages.confirmDelete')",
                cancelButtonText: "@lang('app.cancel')",
                customClass: {
                    confirmButton: 'btn btn-primary mr-3',
                    cancelButton: 'btn btn-secondary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (!result.isConfirmed) return;

                let url = "{{ route('custom-pages.destroy', ':id') }}".replace(':id', id);

                $.easyAjax({
                    type: 'POST',
                    url: url,
                    blockUI: true,
                    data: {
                        _token: "{{ csrf_token() }}",
                        _method: 'DELETE'
                    },
                    success: function (response) {
                        if (response.status === 'success') {
                            $('.row' + id).fadeOut();
                        }
                    }
                });
            });
        });
    </script>
@endpush
