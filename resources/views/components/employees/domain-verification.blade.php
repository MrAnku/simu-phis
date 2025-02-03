<button type="button" id="newDomainVerificationModalBtn"
    class="btn btn-primary mb-2 btn-wave waves-effect waves-light">Verify a new domain</button>
<div class="table-responsive">
    <table id="domainVerificationTable" class="table table-bordered text-nowrap w-100">
        <thead>
            <tr>
                <th>Domain Name</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="allDomains">
            @forelse ($allDomains as $domain)
                <tr>
                    <td>{{ $domain->domain }}</td>
                    <td>
                        @if ($domain->verified == 1)
                            <span class="badge bg-success">Verified</span>
                        @else
                            <span class="badge bg-warning">Pending</span>
                        @endif
                    </td>
                    <td>
                        <span role="button" onclick="deleteDomain(`{{ $domain->domain }}`)"><i
                                class="bx bx-x fs-25"></i></span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="text-center" colspan="5">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
