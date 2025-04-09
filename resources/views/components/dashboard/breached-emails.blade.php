<div class="card custom-card">
    <div class="card-header">
        <div class="card-title">
            {{ __('Dark Web Activity (5 Most Recent Breaches)') }}
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table text-nowrap text-center">
                <thead>
                    <tr>
                        <th scope="col">{{ __('Employee Email') }}</th>
                        <th scope="col">{{ __('Name') }}</th>
                        <th scope="col">{{ __('Breached') }}</th>
                        <th scope="col">{{ __('Website') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($breachedEmails as $email)

                        @php
                            $breachDescs = json_decode($email->data, true);
                        @endphp
                        <tr>
                            <td>{{ $email->email }}</td>
                            <td>{{ $email->userData->user_name }}</td>
                            <td>
                                <span class="badge bg-danger">{{ __('Yes') }}</span>
                            </td>
                            <td class="d-flex gap-2 flex-wrap justify-content-center">
                                @forelse($breachDescs as $desc)
                                    <span
                                        class="badge bg-danger-transparent">{{ $desc['Name'] ?? '' }}</span>
                                @empty
                                @endforelse
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">{{ __('No data found') }}</td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>
    </div>
</div>