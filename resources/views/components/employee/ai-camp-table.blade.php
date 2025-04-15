<div class="card custom-card shadow">
    <div class="card-header justify-content-between">
        <div class="card-title">
            {{ __('Campaigns') }}
        </div>
    </div>
    <div class="card-body pb-3 px-2">
        <div class="table-responsive">
            <table class="table table-bordered text-nowrap w-100">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Employee Score') }}</th>
                        <th>{{ __('Training') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>

                    @forelse($employee->aiCalls as $campaign)
                        <tr>
                            <td>{{ $campaign->campaign_name }}</td>
                            <td class="text-muted">
                                {{ \Carbon\Carbon::parse($campaign->created_at)->format('d/m/Y') }}
                            </td>
                            <td class="text-muted">

                                @if (
                                    $campaign->trainingAssigned &&
                                        $campaign->trainingAssigned->personal_best !== null &&
                                        $campaign->trainingAssigned->personal_best > 0)
                                    {{ $campaign->trainingAssigned->personal_best . '%' }}
                                @else
                                    0%
                                @endif

                            </td>
                            <td class="text-muted">
                                @if ($campaign->training !== null)
                                    <span
                                        class="badge bg-primary-transparent">{{ $campaign->trainingData->name ?? '' }}</span>
                                @else
                                    <span class="badge bg-dark-transparent">
                                        {{ __('Simulation without training') }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($campaign->camp->status == 'pending')
                                    <span class="badge bg-warning">{{ __('Pending') }}</span>
                                @elseif($campaign->camp->status == 'waiting')
                                    <span class="badge bg-warning">{{ __('Waiting') }}</span>
                                @else
                                    <span class="badge bg-success">{{ __('Completed') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">{{ __('No Campaigns') }}</td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>
    </div>
</div>
