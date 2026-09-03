package com.nativeui.plugins.wheeldatepicker.ui

import androidx.compose.foundation.ExperimentalFoundationApi
import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.gestures.snapping.rememberSnapFlingBehavior
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
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import com.nativephp.mobile.ui.nativerender.NativeUIBridge
import com.nativephp.mobile.ui.nativerender.NativeUINode
import kotlinx.coroutines.launch
import java.time.LocalDate
import java.time.YearMonth
import java.time.format.DateTimeFormatter

/**
 * Renders the `wheel_date_picker` EDGE element on Android.
 *
 * Compose has no built-in "wheel" picker (unlike SwiftUI's `.pickerStyle(.wheel)`),
 * so this hand-rolls one: three LazyColumns with snap-fling behavior, a shared
 * center highlight bar, and settle detection driven by scroll-in-progress.
 */
object WheelDatePickerRenderer {

    private const val VISIBLE_ITEMS = 5
    private val ROW_HEIGHT: Dp = 44.dp

    @OptIn(ExperimentalFoundationApi::class)
    @Composable
    fun Render(node: NativeUINode, modifier: Modifier) {
        val p = node.props

        val initialValue = p.getString("value", LocalDate.now().toString())
        val title = p.getString("title", "Select date")
        val yearStart = p.getInt("year_start", 1990)
        val yearEnd = p.getInt("year_end", LocalDate.now().year + 20)
        val showFooter = p.getBool("show_footer", true)

        val colorCard = p.getColor("color_card", Color(0xFF151B30))
        val colorBorder = p.getColor("color_border", Color(0xFF232A45))
        val colorText = p.getColor("color_text", Color(0xFFE7EAF5))
        val colorMuted = p.getColor("color_muted", Color(0xFF5B6280))
        val colorAccent = p.getColor("color_accent", Color(0xFF7C9BFF))

        val onChangeCb = p.getCallbackId("on_change")
        val onDoneCb = p.getCallbackId("on_done")
        val onCancelCb = p.getCallbackId("on_cancel")

        var selectedDate by remember(node.id) {
            mutableStateOf(runCatching { LocalDate.parse(initialValue) }.getOrDefault(LocalDate.now()))
        }

        val years = remember(yearStart, yearEnd) { (yearStart..yearEnd).toList() }
        val months = remember {
            (1..12).map { LocalDate.of(2000, it, 1).format(DateTimeFormatter.ofPattern("MMMM")) }
        }
        val daysInMonth = remember(selectedDate.year, selectedDate.monthValue) {
            YearMonth.of(selectedDate.year, selectedDate.monthValue).lengthOfMonth()
        }
        val days = remember(daysInMonth) { (1..daysInMonth).map { it.toString() } }

        val dayListState = rememberLazyListState()
        val monthListState = rememberLazyListState()
        val yearListState = rememberLazyListState()
        val scope = rememberCoroutineScope()

        fun emitChange() {
            NativeUIBridge.sendTextChangeEvent(onChangeCb, node.id, selectedDate.toString())
        }

        Column(
            modifier = modifier
                .background(colorCard, RoundedCornerShape(20.dp))
                .border(1.dp, colorBorder, RoundedCornerShape(20.dp))
                .padding(16.dp)
        ) {
            Text(
                text = title,
                color = colorText,
                fontWeight = FontWeight.SemiBold,
                fontSize = 16.sp,
                modifier = Modifier.padding(bottom = 12.dp)
            )

            Row(
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.SpaceBetween,
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(bottom = 12.dp)
            ) {
                Text(
                    text = "‹",
                    color = colorAccent,
                    fontSize = 20.sp,
                    modifier = Modifier
                        .clickable { selectedDate = selectedDate.minusMonths(1); emitChange() }
                        .padding(8.dp)
                )
                Text(
                    text = selectedDate.format(DateTimeFormatter.ofPattern("MMMM yyyy")),
                    color = colorText,
                    fontWeight = FontWeight.Medium,
                    fontSize = 15.sp
                )
                Text(
                    text = "›",
                    color = colorAccent,
                    fontSize = 20.sp,
                    modifier = Modifier
                        .clickable { selectedDate = selectedDate.plusMonths(1); emitChange() }
                        .padding(8.dp)
                )
            }

            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .height(ROW_HEIGHT * VISIBLE_ITEMS)
            ) {
                Box(
                    modifier = Modifier
                        .align(Alignment.Center)
                        .fillMaxWidth()
                        .height(ROW_HEIGHT)
                        .background(colorAccent.copy(alpha = 0.12f), RoundedCornerShape(10.dp))
                )

                Row(modifier = Modifier.fillMaxSize()) {
                    WheelColumn(
                        modifier = Modifier.weight(1f),
                        items = days,
                        listState = dayListState,
                        selectedIndex = (selectedDate.dayOfMonth - 1).coerceIn(0, days.lastIndex),
                        textColor = colorText,
                        mutedColor = colorMuted,
                        onSettled = { index ->
                            val day = (index + 1).coerceIn(1, daysInMonth)
                            selectedDate = selectedDate.withDayOfMonth(day)
                            emitChange()
                        }
                    )
                    WheelColumn(
                        modifier = Modifier.weight(1f),
                        items = months,
                        listState = monthListState,
                        selectedIndex = selectedDate.monthValue - 1,
                        textColor = colorText,
                        mutedColor = colorMuted,
                        onSettled = { index ->
                            val month = (index + 1).coerceIn(1, 12)
                            val maxDay = YearMonth.of(selectedDate.year, month).lengthOfMonth()
                            selectedDate = selectedDate.withMonth(month)
                                .withDayOfMonth(selectedDate.dayOfMonth.coerceAtMost(maxDay))
                            emitChange()
                        }
                    )
                    WheelColumn(
                        modifier = Modifier.weight(1f),
                        items = years.map { it.toString() },
                        listState = yearListState,
                        selectedIndex = years.indexOf(selectedDate.year).coerceAtLeast(0),
                        textColor = colorText,
                        mutedColor = colorMuted,
                        onSettled = { index ->
                            val year = years.getOrElse(index) { selectedDate.year }
                            val maxDay = YearMonth.of(year, selectedDate.monthValue).lengthOfMonth()
                            selectedDate = selectedDate.withYear(year)
                                .withDayOfMonth(selectedDate.dayOfMonth.coerceAtMost(maxDay))
                            emitChange()
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
                        color = colorAccent,
                        fontSize = 14.sp,
                        modifier = Modifier.clickable {
                            selectedDate = LocalDate.now()
                            emitChange()
                            scope.launch {
                                dayListState.animateScrollToItem((selectedDate.dayOfMonth - 1).coerceAtLeast(0))
                                monthListState.animateScrollToItem(selectedDate.monthValue - 1)
                                yearListState.animateScrollToItem(years.indexOf(selectedDate.year).coerceAtLeast(0))
                            }
                        }
                    )
                    Row {
                        Text(
                            text = "Cancel",
                            color = colorMuted,
                            fontSize = 14.sp,
                            modifier = Modifier
                                .clickable {
                                    NativeUIBridge.sendTextChangeEvent(
                                        onCancelCb, node.id, selectedDate.toString()
                                    )
                                }
                                .padding(end = 16.dp)
                        )
                        Text(
                            text = "Done",
                            color = colorAccent,
                            fontWeight = FontWeight.SemiBold,
                            fontSize = 14.sp,
                            modifier = Modifier.clickable {
                                NativeUIBridge.sendTextChangeEvent(
                                    onDoneCb, node.id, selectedDate.toString()
                                )
                            }
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
        textColor: Color,
        mutedColor: Color,
        onSettled: (Int) -> Unit
    ) {
        val paddingItems = VISIBLE_ITEMS / 2

        LaunchedEffect(selectedIndex, items.size) {
            if (items.isNotEmpty() && listState.firstVisibleItemIndex != selectedIndex) {
                listState.scrollToItem(selectedIndex.coerceIn(0, items.lastIndex))
            }
        }

        LaunchedEffect(listState) {
            snapshotFlow { listState.isScrollInProgress }
                .collect { scrolling ->
                    if (!scrolling && items.isNotEmpty()) {
                        onSettled(listState.firstVisibleItemIndex.coerceIn(0, items.lastIndex))
                    }
                }
        }

        LazyColumn(
            state = listState,
            flingBehavior = rememberSnapFlingBehavior(listState),
            modifier = modifier.height(ROW_HEIGHT * VISIBLE_ITEMS),
            contentPadding = PaddingValues(vertical = ROW_HEIGHT * paddingItems)
        ) {
            itemsIndexed(items) { index, label ->
                val isSelected = index == selectedIndex
                Box(
                    modifier = Modifier
                        .fillMaxWidth()
                        .height(ROW_HEIGHT),
                    contentAlignment = Alignment.Center
                ) {
                    Text(
                        text = label,
                        color = if (isSelected) textColor else mutedColor,
                        fontWeight = if (isSelected) FontWeight.SemiBold else FontWeight.Normal,
                        fontSize = if (isSelected) 17.sp else 15.sp,
                        textAlign = TextAlign.Center
                    )
                }
            }
        }
    }
}
