<div class="page">
    <div class="page-header">
        <div class="page-title">Services</div>
        <a href="{{ route('services.create') }}" class="btn btn-primary">+ New service</a>
    </div>
    <div class="card overflow-x-auto">
        <table class="data-table">
            <colgroup>
                <col>
                <col style="width: 12rem">
                <col style="width: 5rem">
                <col style="width: 7rem">
            </colgroup>
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Entity</th>
                    <th>Status</th>
                    <th class="right">Rate</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($services as $service)
                    <tr class="is-link" onclick="window.location='{{ route('services.edit', $service) }}'">
                        <td class="strong truncate">{{ $service->name }}</td>
                        <td class="muted truncate">{{ $service->billingEntity?->name ?? 'All entities' }}</td>
                        <td class="muted">{{ $service->is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="strong num right">{{ $service->formattedRate() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="data-table-empty">No services yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
