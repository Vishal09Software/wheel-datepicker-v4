<native:column class="p-4 gap-3">
    <native:wheel-date-picker
        :value="$date"
        title="Select date"
        year-start="2020"
        year-end="2035"
        _done="onDateDone"
        class="w-full"
    />
</native:column>
