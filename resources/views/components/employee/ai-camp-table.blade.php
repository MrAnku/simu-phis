<div class="card custom-card shadow">
    <div class="card-header justify-content-between">
        <div class="card-title">
            Campaigns
        </div>
    </div>
    <div class="card-body pb-3 px-2">
        <div class="table-responsive">
            <table class="table table-bordered text-nowrap w-100">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Employee Score</th>
                        <th>Training</th>
                        <th>Status</th>
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
                                        Simulation without training
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($campaign->camp->status == 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($campaign->camp->status == 'waiting')
                                    <span class="badge bg-warning">Waiting</span>
                                @else
                                    <span class="badge bg-success">Completed</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No Campaigns</td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>
    </div>
</div>
