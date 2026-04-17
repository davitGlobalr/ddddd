@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.css" crossorigin="anonymous">
@endpush

@section('content')
<div class="container">
    <h1 class="h3 mb-4">{{ __('Admin — Bookings') }}</h1>

    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Bootstrap Table: search + pagination (server-side via data-url) --}}
    <table
        id="bookings-table"
        class="table table-striped table-bordered table-hover"
        data-toggle="table"
        data-search="true"
        data-pagination="true"
        data-side-pagination="server"
        data-page-size="5"
        data-page-list="[5, 10, 15, 20]"
        data-url="{{ $tableUrl }}"
        data-query-params="bookingsQueryParams"
    >
        <thead>
            <tr>
                <th data-field="id">ID</th>
                <th data-field="user_id">{{ __('User ID') }}</th>
                <th data-field="user_name">{{ __('User name') }}</th>
                <th data-field="book_id">{{ __('Book ID') }}</th>
                <th data-field="book_name">{{ __('Book name') }}</th>
                <th data-field="status" data-formatter="bookingsStatusFormatter" data-escape="false">{{ __('Status') }}</th>
                <th data-field="actions" data-formatter="bookingsActionsFormatter" data-escape="false">{{ __('Actions') }}</th>
            </tr>
        </thead>
    </table>
</div>

{{-- Modal: change status to 2 (approve) or 3 (cancel) --}}
<div class="modal fade" id="changeBookingStatusModal" tabindex="-1" aria-labelledby="changeBookingStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeBookingStatusModalLabel">{{ __('Change booking status') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1"><strong>{{ __('User') }}:</strong> <span id="modal-user-label"></span></p>
                <p class="mb-0"><strong>{{ __('Book') }}:</strong> <span id="modal-book-label"></span></p>
            </div>
            <div class="modal-footer flex-column gap-2 align-items-stretch">
                <form id="modal-booking-status-form" method="POST" action="">
                    @csrf
                    @method('PATCH')
                    <div class="d-grid gap-2">
                        <button type="submit" name="status" value="2" class="btn btn-success">{{ __('Approve (status 2)') }}</button>
                        <button type="submit" name="status" value="3" class="btn btn-danger">{{ __('Cancel (status 3)') }}</button>
                    </div>
                </form>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <script>
        window.bookingsQueryParams = function (params) { return params; };

        window.escapeHtml = function (value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };

        window.bookingsStatusFormatter = function (value) {
            var status = String(value ?? '');
            var label = status;
            var badge = 'secondary';

            if (status === '1') { label = 'Pending'; badge = 'warning'; }
            if (status === '2') { label = 'Approved'; badge = 'success'; }
            if (status === '3') { label = 'Cancelled'; badge = 'secondary'; }

            return '<span class="badge bg-' + badge + '">' + escapeHtml(label) + ' (' + escapeHtml(status) + ')</span>';
        };

        window.bookingsActionsFormatter = function (_value, row) {
            if (String(row.status ?? '') !== '1') {
                return '<span class="text-muted small">—</span>';
            }

            var bookingId = encodeURIComponent(String(row.id ?? ''));

            return '' +
                '<button type="button" class="btn btn-sm btn-outline-primary js-open-status-modal" ' +
                'data-bs-toggle="modal" data-bs-target="#changeBookingStatusModal" ' +
                'data-booking-id="' + bookingId + '">' +
                'Change status' +
                '</button>';
        };
    </script>
    <script src="https://unpkg.com/bootstrap-table@1.22.1/dist/bootstrap-table.min.js" crossorigin="anonymous"></script>
    <script>
        (function () {
            $(document).on('click', '.js-open-status-modal', function () {
                var button = $(this);
                var row = button.closest('tr');
                if (row.length === 0) {
                    return;
                }

                var bookingId = $.trim(row.find('td').eq(0).text());
                var userId = $.trim(row.find('td').eq(1).text());
                var userName = $.trim(row.find('td').eq(2).text());
                var bookId = $.trim(row.find('td').eq(3).text());
                var bookName = $.trim(row.find('td').eq(4).text());

                if (bookingId === '') {
                    return;
                }

                var userLabel = userId + ' — ' + (userName || '—');
                var bookLabel = bookId + ' — ' + (bookName || '—');

                $('#modal-user-label').text(userLabel);
                $('#modal-book-label').text(bookLabel);
                $('#modal-booking-status-form').attr(
                    'action',
                    '{{ url('/admin/bookings') }}/' + encodeURIComponent(bookingId)
                );
            });
        })();
    </script>
@endpush
