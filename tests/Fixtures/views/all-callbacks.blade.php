<native:column class="p-4 gap-3">
    <native:wheel-date-picker
        :value="$date"
        _change="onDateChange"
        _done="onDateDone"
        _cancel="onDateCancel"
        class="w-full"
    />
</native:column>
