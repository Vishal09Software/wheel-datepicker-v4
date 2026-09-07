<native:column class="p-4 gap-3">
    <native:wheel-date-picker
        :value="$date"
        on-change="onDateChange"
        on-done="onDateDone"
        on-cancel="onDateCancel"
        class="w-full"
    />
</native:column>
