{{--
    Recipient search results (#236) — rendered by MailboxController@recipientsSearch
    and injected into #recipientsResults via AJAX. Per-row checkbox (.recipient-check)
    drives the persistent selection Set in compose.blade.php; it is NOT named to[]
    directly so pagination/search never drop earlier selections.
--}}
@if($recipients->isEmpty())
    <div class="text-muted" style="padding:.6rem .2rem">@lang('mailbox.no_recipients_found')</div>
@else
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th style="width:36px"><input type="checkbox" id="recipientPageAll" aria-label="@lang('mailbox.select_page')"></th>
                    <th>@lang('mailbox.recipient_name')</th>
                    <th>@lang('mailbox.recipient_roles')</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recipients as $u)
                    <tr>
                        <td>
                            <input type="checkbox" class="recipient-check"
                                   value="{{ $u->id }}" data-name="{{ $u->name }}"
                                   aria-label="{{ $u->name }}">
                        </td>
                        <td>{{ $u->name }}</td>
                        <td>
                            @foreach($u->roles as $r)
                                <span class="badge badge-light">{{ $r->name }}</span>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($recipients->hasPages())
        <div id="recipientPagination" style="padding:.5rem .2rem 0">
            {{ $recipients->links() }}
        </div>
    @endif
@endif
