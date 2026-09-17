<div>
    <livewire:core-shared-components::data-table
        model="Bale\Api\Models\ApiToken"
        rowView="api::livewire.pages.token.section.token-row"
        :columns="[
            [
                'key'      => 'name',
                'label'    => __('Name'),
                'sortable' => true,
            ],
            [
                'key'      => 'abilities',
                'label'    => __('Permissions'),
                'sortable' => false,
            ],
            [
                'key'      => 'last_used_at',
                'label'    => __('Last Used'),
                'sortable' => true,
            ],
            [
                'key'      => 'expires_at',
                'label'    => __('Expires'),
                'sortable' => true,
            ],
            [
                'key'      => 'status',
                'label'    => __('Status'),
                'sortable' => false,
            ],
            [
                'key'      => 'created_at',
                'label'    => __('Created'),
                'sortable' => true,
            ],
            [
                'key'      => 'actions',
                'label'    => '',
                'sortable' => false,
            ],
        ]"
        :searchable="['name']"
        sortField="created_at"
        sortDirection="desc"
        :perPage="20"
    />
</div>