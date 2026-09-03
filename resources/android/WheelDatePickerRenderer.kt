package com.nativeui.plugins.wheeldatepicker.ui

import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.gestures.snapping.rememberSnapFlingBehavior
import androidx.compose.foundation.isSystemInDarkTheme
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.PaddingValues
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.LazyListState
import androidx.compose.foundation.lazy.itemsIndexed
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.OutlinedTextFieldDefaults
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.runtime.snapshotFlow
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.semantics.contentDescription
import androidx.compose.ui.semantics.semantics
import androidx.compose.ui.text.TextStyle
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.ui.window.Dialog
import com.nativephp.mobile.ui.MaterialIcon
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import com.nativephp.plugins.native_ui.NativeUITheme
import kotlin.math.abs
import kotlinx.coroutines.launch
import java.time.LocalDate
import java.time.YearMonth
import java.time.format.DateTimeFormatter

/**
 * Compact trigger field + wheel dialog. Day 31 stays selected because
 * settle uses the item nearest the viewport center, not firstVisibleItemIndex.
 */
object WheelDatePickerRenderer {

    private data class WheelSize(
        val rowHeight: Dp,
        val visibleItems: Int,
        val wheelHeight: Dp,
        val cardPadding: Dp,
        val cornerRadius: Dp,
        val selectedFontSp: Int,
        val mutedFontSp: Int,
    )

    private val DialogCard = Color(0xFFFFFFFF)
    private val DialogBorder = Color(0xFF79747E)
    private val DialogText = Color(0xFF1C1B1F)
    private val DialogMuted = Color(0xFF49454F)
    private val DialogAccent = Color(0xFF6750A4)

    @OptIn(ExperimentalFoundationApi::class)
    @Composable
    fun Render(node: NativeUINode, modifier: Modifier) {
        val p = node.props
        val theme = if (isSystemInDarkTheme()) NativeUITheme.dark else NativeUITheme.light

        val serverValue = p.getString("value")
        val title = p.getString("title", "Select date")
        val label = p.getString("label")
        val placeholder = p.getString("placeholder", "Select date")
        val pattern = p.getString("pattern", "yyyy-MM-dd")
        val pickerStyle = p.getString("picker_style", "compact")
        val confirmLabel = p.getString("confirm_label", "Done")
        val cancelLabel = p.getString("cancel_label", "Cancel")
        val yearStart = p.getInt("year_start", 1990)
        val yearEnd = p.getInt("year_end", LocalDate.now().year + 20)
        val showFooter = p.getBool("show_footer", pickerStyle == "inline")
        val a11yLabel = p.getString("a11y_label").ifEmpty { label.ifEmpty { title } }
        val compact = pickerStyle != "inline"

        val dialogCard = parseHex(p.getString("color_card"), DialogCard)
        val dialogBorder = parseHex(p.getString("color_border"), DialogBorder)
        val dialogText = parseHex(p.getString("color_text"), DialogText)
        val dialogMuted = parseHex(p.getString("color_muted"), DialogMuted)
        val dialogAccent = parseHex(p.getString("color_accent"), DialogAccent)

        val rowHeightDp = p.getInt("row_height", 44).coerceIn(24, 80)
        val visibleItems = when (val count = p.getInt("visible_items", 5)) {
            3, 5, 7 -> count
            else -> if (count <= 3) 3 else if (count >= 7) 7 else 5
        }
        val wheelHeightRaw = p.getInt("wheel_height", 0)
        val wheelSize = WheelSize(
            rowHeight = rowHeightDp.dp,
            visibleItems = visibleItems,
            wheelHeight = if (wheelHeightRaw > 0) wheelHeightRaw.dp else rowHeightDp.dp * visibleItems,
            cardPadding = p.getInt("card_padding", 16).coerceIn(0, 48).dp,
            cornerRadius = p.getInt("corner_radius", 20).coerceIn(0, 48).dp,
            selectedFontSp = p.getInt("selected_font", 20).coerceIn(12, 32),
            mutedFontSp = p.getInt("muted_font", 15).coerceIn(10, 24),
        )

        val onChangeCb = p.getCallbackId("on_change")
        val onDoneCb = p.getCallbackId("on_done")
        val onCancelCb = p.getCallbackId("on_cancel")

        var committed by remember(node.id) { mutableStateOf(serverValue) }
        var lastSentValue by remember(node.id) { mutableStateOf(serverValue) }
        var showDialog by remember { mutableStateOf(false) }
        var draftDate by remember { mutableStateOf(parseDate(serverValue, pattern)) }

        LaunchedEffect(serverValue) {
            if (serverValue != lastSentValue) {
                committed = serverValue
                lastSentValue = serverValue
                draftDate = parseDate(serverValue, pattern)
            }
        }

        fun emit(callbackId: Int, date: LocalDate, force: Boolean = false) {
            val formatted = formatDate(date, pattern)
            if (!force && formatted == lastSentValue) {
                return
            }
            lastSentValue = formatted
            committed = formatted
            if (callbackId != 0) {
                NativeUIBridge.sendSelectChangeEvent(callbackId, node.id, formatted)
            }
        }

        val shown = if (committed.isEmpty()) "" else committed

        Column(modifier = modifier.semantics { if (a11yLabel.isNotEmpty()) contentDescription = a11yLabel }) {
            if (compact) {
                if (label.isNotEmpty()) {
                    Text(
                        text = label,
                        color = theme.onSurfaceVariant,
                        fontSize = 14.sp,
                        modifier = Modifier.padding(bottom = 4.dp)
                    )
                }
                Box {
                    OutlinedTextField(
                        value = shown,
                        onValueChange = {},
                        readOnly = true,
                        placeholder = { Text(placeholder) },
                        trailingIcon = {
                            MaterialIcon(
                                name = "calendar",
                                contentDescription = null,
                                tint = theme.onSurfaceVariant,
                            )
                        },
                        modifier = Modifier.fillMaxWidth(),
                        textStyle = TextStyle(color = theme.onSurface),
                        colors = OutlinedTextFieldDefaults.colors(
                            focusedTextColor = theme.onSurface,
                            unfocusedTextColor = theme.onSurface,
                            focusedBorderColor = theme.primary,
                            unfocusedBorderColor = theme.outline,
                            focusedPlaceholderColor = theme.onSurfaceVariant,
                            unfocusedPlaceholderColor = theme.onSurfaceVariant,
                        ),
                    )
                    Box(
                        Modifier
                            .matchParentSize()
                            .clickable {
                                draftDate = parseDate(committed, pattern)
                                showDialog = true
                            }
                    )
                }
            } else {
                WheelCard(
                    title = title,
                    selectedDate = draftDate,
                    yearStart = yearStart,
                    yearEnd = yearEnd,
                    showFooter = showFooter,
                    confirmLabel = confirmLabel,
                    cancelLabel = cancelLabel,
                    size = wheelSize,
                    card = dialogCard,
                    border = dialogBorder,
                    text = dialogText,
                    muted = dialogMuted,
                    accent = dialogAccent,
                    onDateChange = { draftDate = it },
                    onToday = { draftDate = LocalDate.now() },
                    onCancel = { emit(onCancelCb, draftDate, force = true) },
                    onDone = {
                        emit(onChangeCb, draftDate, force = true)
                        emit(onDoneCb, draftDate, force = true)
                    },
                )
            }
        }

        if (compact && showDialog) {
            Dialog(onDismissRequest = { showDialog = false }) {
                WheelCard(
                    title = title,
                    selectedDate = draftDate,
                    yearStart = yearStart,
                    yearEnd = yearEnd,
                    showFooter = true,
                    confirmLabel = confirmLabel,
                    cancelLabel = cancelLabel,
                    size = wheelSize,
                    card = dialogCard,
                    border = dialogBorder,
                    text = dialogText,
                    muted = dialogMuted,
                    accent = dialogAccent,
                    onDateChange = { draftDate = it },
                    onToday = { draftDate = LocalDate.now() },
                    onCancel = {
                        showDialog = false
                        draftDate = parseDate(committed, pattern)
                        if (onCancelCb != 0) {
                            NativeUIBridge.sendSelectChangeEvent(onCancelCb, node.id, committed)
                        }
                    },
                    onDone = {
                        showDialog = false
                        emit(onChangeCb, draftDate, force = true)
                        if (onDoneCb != 0) {
                            NativeUIBridge.sendSelectChangeEvent(onDoneCb, node.id, formatDate(draftDate, pattern))
                        }
                    },
                )
            }
        }
    }

    @OptIn(ExperimentalFoundationApi::class)
    @Composable
    private fun WheelCard(
        title: String,
        selectedDate: LocalDate,
        yearStart: Int,
        yearEnd: Int,
        showFooter: Boolean,
        confirmLabel: String,
        cancelLabel: String,
        size: WheelSize,
        card: Color,
        border: Color,
        text: Color,
        muted: Color,
        accent: Color,
        onDateChange: (LocalDate) -> Unit,
        onToday: () -> Unit,
        onCancel: () -> Unit,
        onDone: () -> Unit,
    ) {
        val years = remember(yearStart, yearEnd) { (yearStart..yearEnd).toList() }
        val months = remember {
            (1..12).map {
                LocalDate.of(2000, it, 1).format(DateTimeFormatter.ofPattern("MMM")).uppercase()
            }
        }
        val daysInMonth = YearMonth.of(selectedDate.year, selectedDate.monthValue).lengthOfMonth()
        val days = (1..daysInMonth).map { it.toString() }

        val dayListState = rememberLazyListState()
        val monthListState = rememberLazyListState()
        val yearListState = rememberLazyListState()
        val scope = rememberCoroutineScope()

        Column(
            modifier = Modifier
                .fillMaxWidth()
                .background(card, RoundedCornerShape(size.cornerRadius))
                .border(1.dp, border, RoundedCornerShape(size.cornerRadius))
                .padding(size.cardPadding)
        ) {
            if (title.isNotEmpty()) {
                Text(
                    text = title,
                    color = text,
                    fontWeight = FontWeight.SemiBold,
                    fontSize = 16.sp,
                    modifier = Modifier.padding(bottom = 12.dp)
                )
            }

            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween,
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(bottom = 12.dp)
            ) {
                Text(
                    text = "<",
                    color = accent,
                    fontSize = 20.sp,
                    modifier = Modifier
                        .clickable { onDateChange(selectedDate.minusMonths(1)) }
                        .padding(8.dp)
                        .semantics { contentDescription = "Previous month" }
                )
                Text(
                    text = selectedDate.format(DateTimeFormatter.ofPattern("MMMM yyyy")),
                    color = text,
                    fontWeight = FontWeight.Medium,
                    fontSize = 15.sp
                )
                Text(
                    text = ">",
                    color = accent,
                    fontSize = 20.sp,
                    modifier = Modifier
                        .clickable { onDateChange(selectedDate.plusMonths(1)) }
                        .padding(8.dp)
                        .semantics { contentDescription = "Next month" }
                )
            }

            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .height(size.wheelHeight)
            ) {
                Box(
                    modifier = Modifier
                        .align(Alignment.Center)
                        .fillMaxWidth()
                        .height(size.rowHeight)
                        .background(accent.copy(alpha = 0.16f), RoundedCornerShape(10.dp))
                )

                Row(modifier = Modifier.fillMaxSize()) {
                    WheelColumn(
                        modifier = Modifier.weight(1f),
                        items = days,
                        listState = dayListState,
                        selectedIndex = (selectedDate.dayOfMonth - 1).coerceIn(0, days.lastIndex),
                        size = size,
                        selectedColor = accent,
                        mutedColor = muted,
                        onSettled = { index ->
                            val day = (index + 1).coerceIn(1, daysInMonth)
                            if (day != selectedDate.dayOfMonth) {
                                onDateChange(selectedDate.withDayOfMonth(day))
                            }
                        }
                    )
                    WheelColumn(
                        modifier = Modifier.weight(1f),
                        items = months,
                        listState = monthListState,
                        selectedIndex = selectedDate.monthValue - 1,
                        size = size,
                        selectedColor = accent,
                        mutedColor = muted,
                        onSettled = { index ->
                            val month = (index + 1).coerceIn(1, 12)
                            val maxDay = YearMonth.of(selectedDate.year, month).lengthOfMonth()
                            val next = selectedDate.withMonth(month)
                                .withDayOfMonth(selectedDate.dayOfMonth.coerceAtMost(maxDay))
                            if (next != selectedDate) {
                                onDateChange(next)
                            }
                        }
                    )
                    WheelColumn(
                        modifier = Modifier.weight(1f),
                        items = years.map { it.toString() },
                        listState = yearListState,
                        selectedIndex = years.indexOf(selectedDate.year).coerceAtLeast(0),
                        size = size,
                        selectedColor = accent,
                        mutedColor = muted,
                        onSettled = { index ->
                            val year = years.getOrElse(index) { selectedDate.year }
                            val maxDay = YearMonth.of(year, selectedDate.monthValue).lengthOfMonth()
                            val next = selectedDate.withYear(year)
                                .withDayOfMonth(selectedDate.dayOfMonth.coerceAtMost(maxDay))
                            if (next != selectedDate) {
                                onDateChange(next)
                            }
                        }
                    )
                }
            }

            if (showFooter) {
                Row(
                    modifier = Modifier
                        .fillMaxWidth()
                        .padding(top = 12.dp),
                    horizontalArrangement = Arrangement.SpaceBetween,
                    verticalAlignment = Alignment.CenterVertically
                ) {
                    Text(
                        text = "Today",
                        color = accent,
                        fontSize = 14.sp,
                        modifier = Modifier.clickable {
                            onToday()
                            val today = LocalDate.now()
                            scope.launch {
                                dayListState.scrollToItem((today.dayOfMonth - 1).coerceAtLeast(0))
                                monthListState.scrollToItem(today.monthValue - 1)
                                yearListState.scrollToItem(years.indexOf(today.year).coerceAtLeast(0))
                            }
                        }
                    )
                    Row {
                        Text(
                            text = cancelLabel,
                            color = accent,
                            fontSize = 14.sp,
                            modifier = Modifier
                                .clickable(onClick = onCancel)
                                .padding(end = 16.dp)
                        )
                        Text(
                            text = confirmLabel,
                            color = accent,
                            fontWeight = FontWeight.SemiBold,
                            fontSize = 14.sp,
                            modifier = Modifier.clickable(onClick = onDone)
                        )
                    }
                }
            }
        }
    }

    @OptIn(ExperimentalFoundationApi::class)
    @Composable
    private fun WheelColumn(
        modifier: Modifier,
        items: List<String>,
        listState: LazyListState,
        selectedIndex: Int,
        size: WheelSize,
        selectedColor: Color,
        mutedColor: Color,
        onSettled: (Int) -> Unit
    ) {
        val paddingItems = size.visibleItems / 2
        var ignoreSettle by remember { mutableStateOf(true) }

        LaunchedEffect(selectedIndex, items.size) {
            if (items.isEmpty()) {
                return@LaunchedEffect
            }
            ignoreSettle = true
            val target = selectedIndex.coerceIn(0, items.lastIndex)
            if (centeredIndex(listState, items.lastIndex) != target) {
                listState.scrollToItem(target)
            }
            ignoreSettle = false
        }

        LaunchedEffect(listState, items.size) {
            snapshotFlow { listState.isScrollInProgress }
                .collect { scrolling ->
                    if (!scrolling && !ignoreSettle && items.isNotEmpty()) {
                        onSettled(centeredIndex(listState, items.lastIndex))
                    }
                }
        }

        LazyColumn(
            state = listState,
            flingBehavior = rememberSnapFlingBehavior(listState),
            modifier = modifier.height(size.wheelHeight),
            contentPadding = PaddingValues(vertical = size.rowHeight * paddingItems)
        ) {
            itemsIndexed(items) { index, label ->
                val isSelected = index == selectedIndex
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(size.rowHeight),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = label,
                        color = if (isSelected) selectedColor else mutedColor,
                        fontWeight = if (isSelected) FontWeight.SemiBold else FontWeight.Normal,
                        fontSize = if (isSelected) size.selectedFontSp.sp else size.mutedFontSp.sp,
                        textAlign = TextAlign.Center
                    )
                }
            }
        }
    }

    private fun centeredIndex(state: LazyListState, lastIndex: Int): Int {
        val info = state.layoutInfo
        val visible = info.visibleItemsInfo
        if (visible.isEmpty()) {
            return 0
        }

        val center = (info.viewportStartOffset + info.viewportEndOffset) / 2
        val nearest = visible.minByOrNull { item ->
            abs((item.offset + item.size / 2) - center)
        }?.index ?: 0

        return nearest.coerceIn(0, lastIndex)
    }

    private fun parseDate(raw: String, pattern: String): LocalDate {
        if (raw.isEmpty()) {
            return LocalDate.now()
        }

        runCatching { LocalDate.parse(raw, DateTimeFormatter.ofPattern(pattern)) }.getOrNull()?.let { return it }
        runCatching { LocalDate.parse(raw) }.getOrNull()?.let { return it }
        runCatching { LocalDate.parse(raw, DateTimeFormatter.ofPattern("dd-MM-yyyy")) }.getOrNull()?.let { return it }
        runCatching { LocalDate.parse(raw, DateTimeFormatter.ofPattern("dd/MM/yyyy")) }.getOrNull()?.let { return it }

        return LocalDate.now()
    }

    private fun formatDate(date: LocalDate, pattern: String): String =
        date.format(DateTimeFormatter.ofPattern(pattern))

    private fun parseHex(hex: String, fallback: Color): Color {
        if (hex.isEmpty() || !hex.startsWith("#")) {
            return fallback
        }

        val clean = hex.removePrefix("#")
        val long = try {
            clean.toLong(16)
        } catch (_: NumberFormatException) {
            return fallback
        }

        val argb: Long = when (clean.length) {
            6 -> 0xFF000000L or long
            8 -> long
            else -> return fallback
        }

        return Color(
            alpha = ((argb shr 24) and 0xFF) / 255f,
            red = ((argb shr 16) and 0xFF) / 255f,
            green = ((argb shr 8) and 0xFF) / 255f,
            blue = (argb and 0xFF) / 255f,
        )
    }
}
