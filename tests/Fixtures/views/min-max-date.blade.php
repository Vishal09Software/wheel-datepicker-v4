<native:column class="p-4 gap-3">
    <native:wheel-date-picker
        :value="$date"
        title="Select date"
        min-date="2000-01-01"
        max-date="2026-12-31"
        locale="fr-FR"
        year-start="1990"
        year-end="2040"
        class="w-full"
    />
</native:column>
