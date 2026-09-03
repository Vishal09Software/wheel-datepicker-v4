import SwiftUI

/// Compact date field that opens a dark wheel sheet. Confirms only on Done.
struct WheelDatePickerRenderer: View {
    let node: NativeUINode

    @ObservedObject private var themeStore = NativeUITheme.shared
    @Environment(\.colorScheme) private var colorScheme

    @State private var committed: String = ""
    @State private var lastSentValue: String = ""
    @State private var draftDate: Date = Date()
    @State private var showSheet: Bool = false
    @State private var initialized: Bool = false

    var body: some View {
        let theme = themeStore.resolve(for: colorScheme)
        let props = node.props

        let title = props.getString("title", default: "Select date")
        let label = props.getString("label", default: "")
        let placeholder = props.getString("placeholder", default: "Select date")
        let pattern = props.getString("pattern", default: "yyyy-MM-dd")
        let pickerStyle = props.getString("picker_style", default: "compact")
        let confirmLabel = props.getString("confirm_label", default: "Done")
        let cancelLabel = props.getString("cancel_label", default: "Cancel")
        let yearStart = props.getInt("year_start", default: 1990)
        let yearEnd = props.getInt("year_end", default: Calendar.current.component(.year, from: Date()) + 20)
        let compact = pickerStyle != "inline"
        let showFooter = props.getBool("show_footer", default: !compact)
        let a11yLabel = props.getString("a11y_label")
        let a11yHint = props.getString("a11y_hint")
        let serverValue = props.getString("value")

        let dialogCard = color(props.getString("color_card"), fallback: Self.hexColor("#FFFFFF") ?? .white)
        let dialogBorder = color(props.getString("color_border"), fallback: Self.hexColor("#79747E") ?? .gray)
        let dialogText = color(props.getString("color_text"), fallback: Self.hexColor("#1C1B1F") ?? .primary)
        let dialogMuted = color(props.getString("color_muted"), fallback: Self.hexColor("#49454F") ?? .secondary)
        let dialogAccent = color(props.getString("color_accent"), fallback: Self.hexColor("#6750A4") ?? .purple)

        let rowHeight = CGFloat(max(24, min(80, props.getInt("row_height", default: 44))))
        let visibleRaw = props.getInt("visible_items", default: 5)
        let visibleItems: CGFloat = {
            switch visibleRaw {
            case 3, 5, 7: return CGFloat(visibleRaw)
            default: return visibleRaw <= 3 ? 3 : (visibleRaw >= 7 ? 7 : 5)
            }
        }()
        let wheelHeightRaw = props.getInt("wheel_height", default: 0)
        let wheelHeight = wheelHeightRaw > 0 ? CGFloat(wheelHeightRaw) : rowHeight * visibleItems
        let cardPadding = CGFloat(max(0, min(48, props.getInt("card_padding", default: 16))))
        let cornerRadius = CGFloat(max(0, min(48, props.getInt("corner_radius", default: 20))))
        let selectedFont = CGFloat(max(12, min(32, props.getInt("selected_font", default: 20))))
        let mutedFont = CGFloat(max(10, min(24, props.getInt("muted_font", default: 15))))

        let onChangeCb = props.getCallbackId("on_change")
        let onDoneCb = props.getCallbackId("on_done")
        let onCancelCb = props.getCallbackId("on_cancel")

        VStack(alignment: .leading, spacing: 4) {
            if compact {
                if !label.isEmpty {
                    Text(label)
                        .font(.system(size: theme.fontSm, weight: .medium))
                        .foregroundStyle(theme.onSurfaceVariant)
                }

                Button {
                    draftDate = Self.parse(committed.isEmpty ? serverValue : committed, pattern: pattern)
                    showSheet = true
                } label: {
                    HStack {
                        Text(committed.isEmpty ? placeholder : committed)
                            .foregroundStyle(committed.isEmpty ? theme.onSurfaceVariant : theme.onSurface)
                        Spacer()
                        Image(systemName: "calendar")
                            .foregroundStyle(theme.onSurfaceVariant)
                    }
                    .padding(.horizontal, 12)
                    .padding(.vertical, 11)
                    .background(
                        RoundedRectangle(cornerRadius: theme.radiusMd, style: .continuous)
                            .stroke(theme.outline, lineWidth: 1)
                    )
                }
            } else {
                wheelCard(
                    title: title,
                    yearStart: yearStart,
                    yearEnd: yearEnd,
                    showFooter: showFooter,
                    confirmLabel: confirmLabel,
                    cancelLabel: cancelLabel,
                    dialogCard: dialogCard,
                    dialogBorder: dialogBorder,
                    dialogText: dialogText,
                    dialogMuted: dialogMuted,
                    dialogAccent: dialogAccent,
                    wheelHeight: wheelHeight,
                    cardPadding: cardPadding,
                    cornerRadius: cornerRadius,
                    selectedFont: selectedFont,
                    mutedFont: mutedFont,
                    onCancel: { emit(onCancelCb, draftDate, pattern: pattern, force: true) },
                    onDone: {
                        emit(onChangeCb, draftDate, pattern: pattern, force: true)
                        emit(onDoneCb, draftDate, pattern: pattern, force: true)
                    }
                )
            }
        }
        .sheet(isPresented: $showSheet) {
            wheelCard(
                title: title,
                yearStart: yearStart,
                yearEnd: yearEnd,
                showFooter: true,
                confirmLabel: confirmLabel,
                cancelLabel: cancelLabel,
                dialogCard: dialogCard,
                dialogBorder: dialogBorder,
                dialogText: dialogText,
                dialogMuted: dialogMuted,
                dialogAccent: dialogAccent,
                wheelHeight: wheelHeight,
                cardPadding: cardPadding,
                cornerRadius: cornerRadius,
                selectedFont: selectedFont,
                mutedFont: mutedFont,
                onCancel: {
                    showSheet = false
                    draftDate = Self.parse(committed, pattern: pattern)
                    if onCancelCb != 0 {
                        NativeElementBridge.sendSelectChangeEvent(onCancelCb, nodeId: node.id, value: committed)
                    }
                },
                onDone: {
                    showSheet = false
                    emit(onChangeCb, draftDate, pattern: pattern, force: true)
                    if onDoneCb != 0 {
                        NativeElementBridge.sendSelectChangeEvent(
                            onDoneCb,
                            nodeId: node.id,
                            value: Self.format(draftDate, pattern: pattern)
                        )
                    }
                }
            )
            .presentationDetents([.medium, .large])
        }
        .onAppear {
            guard !initialized else { return }
            initialized = true
            committed = serverValue
            lastSentValue = serverValue
            draftDate = Self.parse(serverValue, pattern: pattern)
        }
        .onChange(of: serverValue) { _, newValue in
            if newValue != lastSentValue {
                committed = newValue
                lastSentValue = newValue
                draftDate = Self.parse(newValue, pattern: pattern)
            }
        }
        .accessibilityLabel(a11yLabel.isEmpty ? label : a11yLabel)
        .accessibilityHint(a11yHint)
        .accessibilityValue(committed)
    }

    @ViewBuilder
    private func wheelCard(
        title: String,
        yearStart: Int,
        yearEnd: Int,
        showFooter: Bool,
        confirmLabel: String,
        cancelLabel: String,
        dialogCard: Color,
        dialogBorder: Color,
        dialogText: Color,
        dialogMuted: Color,
        dialogAccent: Color,
        wheelHeight: CGFloat,
        cardPadding: CGFloat,
        cornerRadius: CGFloat,
        selectedFont: CGFloat,
        mutedFont: CGFloat,
        onCancel: @escaping () -> Void,
        onDone: @escaping () -> Void
    ) -> some View {
        let calendar = Self.calendar
        // Defensive: the PHP element normalizes year_start <= year_end before this
        // ever reaches the renderer, but native props can be forged/mocked (e.g.
        // in tests), so guard against a reversed range producing an inverted or
        // single-year list instead of the intended span.
        let years = Array(min(yearStart, yearEnd)...max(yearStart, yearEnd))
        let months = calendar.shortMonthSymbols.map { $0.uppercased() }
        let dayCount = calendar.range(of: .day, in: .month, for: draftDate)?.count ?? 31

        VStack(alignment: .leading, spacing: 12) {
            if !title.isEmpty {
                Text(title)
                    .font(.system(size: 16, weight: .semibold))
                    .foregroundColor(dialogText)
            }

            HStack {
                Button {
                    if let date = calendar.date(byAdding: .month, value: -1, to: draftDate) {
                        draftDate = date
                    }
                } label: {
                    Image(systemName: "chevron.left")
                        .foregroundColor(dialogAccent)
                }
                .accessibilityLabel("Previous month")

                Spacer()

                Text(Self.monthYearFormatter.string(from: draftDate))
                    .font(.system(size: 15, weight: .medium))
                    .foregroundColor(dialogText)

                Spacer()

                Button {
                    if let date = calendar.date(byAdding: .month, value: 1, to: draftDate) {
                        draftDate = date
                    }
                } label: {
                    Image(systemName: "chevron.right")
                        .foregroundColor(dialogAccent)
                }
                .accessibilityLabel("Next month")
            }

            HStack(spacing: 0) {
                Picker("Day", selection: dayBinding(calendar: calendar, dayCount: dayCount)) {
                    ForEach(1...dayCount, id: \.self) { day in
                        Text("\(day)")
                            .font(.system(size: calendar.component(.day, from: draftDate) == day ? selectedFont : mutedFont))
                            .foregroundColor(calendar.component(.day, from: draftDate) == day ? dialogAccent : dialogMuted)
                            .tag(day)
                    }
                }
                .pickerStyle(.wheel)
                .labelsHidden()
                .id("days-\(dayCount)-\(calendar.component(.month, from: draftDate))-\(calendar.component(.year, from: draftDate))")

                Picker("Month", selection: monthBinding(calendar: calendar)) {
                    ForEach(Array(months.enumerated()), id: \.offset) { index, name in
                        Text(name)
                            .font(.system(size: calendar.component(.month, from: draftDate) == index + 1 ? selectedFont : mutedFont))
                            .foregroundColor(calendar.component(.month, from: draftDate) == index + 1 ? dialogAccent : dialogMuted)
                            .tag(index + 1)
                    }
                }
                .pickerStyle(.wheel)
                .labelsHidden()

                Picker("Year", selection: yearBinding(calendar: calendar)) {
                    ForEach(years, id: \.self) { year in
                        Text(String(year))
                            .font(.system(size: calendar.component(.year, from: draftDate) == year ? selectedFont : mutedFont))
                            .foregroundColor(calendar.component(.year, from: draftDate) == year ? dialogAccent : dialogMuted)
                            .tag(year)
                    }
                }
                .pickerStyle(.wheel)
                .labelsHidden()
            }
            .frame(height: wheelHeight)
            .tint(dialogAccent)

            if showFooter {
                HStack {
                    Button("Today") {
                        draftDate = Date()
                    }
                    .font(.system(size: 14))
                    .foregroundColor(dialogAccent)

                    Spacer()

                    Button(cancelLabel, action: onCancel)
                        .font(.system(size: 14))
                        .foregroundColor(dialogAccent)
                        .padding(.trailing, 16)

                    Button(confirmLabel, action: onDone)
                        .font(.system(size: 14, weight: .semibold))
                        .foregroundColor(dialogAccent)
                }
            }
        }
        .padding(cardPadding)
        .background(dialogCard)
        .overlay(
            RoundedRectangle(cornerRadius: cornerRadius).stroke(dialogBorder, lineWidth: 1)
        )
        .clipShape(RoundedRectangle(cornerRadius: cornerRadius))
    }

    private func emit(_ callbackId: Int, _ date: Date, pattern: String, force: Bool) {
        let formatted = Self.format(date, pattern: pattern)
        if !force && formatted == lastSentValue {
            return
        }
        lastSentValue = formatted
        committed = formatted
        if callbackId != 0 {
            NativeElementBridge.sendSelectChangeEvent(callbackId, nodeId: node.id, value: formatted)
        }
    }

    private func dayBinding(calendar: Calendar, dayCount: Int) -> Binding<Int> {
        Binding(
            get: { min(calendar.component(.day, from: draftDate), dayCount) },
            set: { newDay in
                var components = calendar.dateComponents([.year, .month, .day], from: draftDate)
                components.day = min(newDay, dayCount)
                if let date = calendar.date(from: components) {
                    draftDate = date
                }
            }
        )
    }

    private func monthBinding(calendar: Calendar) -> Binding<Int> {
        Binding(
            get: { calendar.component(.month, from: draftDate) },
            set: { newMonth in
                var components = calendar.dateComponents([.year, .month, .day], from: draftDate)
                let maxDayDate = calendar.date(from: DateComponents(calendar: calendar, year: components.year, month: newMonth, day: 1)) ?? draftDate
                let maxDay = calendar.range(of: .day, in: .month, for: maxDayDate)?.count ?? 28
                components.month = newMonth
                components.day = min(components.day ?? 1, maxDay)
                if let date = calendar.date(from: components) {
                    draftDate = date
                }
            }
        )
    }

    private func yearBinding(calendar: Calendar) -> Binding<Int> {
        Binding(
            get: { calendar.component(.year, from: draftDate) },
            set: { newYear in
                var components = calendar.dateComponents([.year, .month, .day], from: draftDate)
                let maxDayDate = calendar.date(from: DateComponents(calendar: calendar, year: newYear, month: components.month, day: 1)) ?? draftDate
                let maxDay = calendar.range(of: .day, in: .month, for: maxDayDate)?.count ?? 28
                components.year = newYear
                components.day = min(components.day ?? 1, maxDay)
                if let date = calendar.date(from: components) {
                    draftDate = date
                }
            }
        )
    }

    private func color(_ hex: String, fallback: Color) -> Color {
        guard !hex.isEmpty else { return fallback }
        return Self.hexColor(hex) ?? fallback
    }

    private static var calendar: Calendar {
        var calendar = Calendar(identifier: .gregorian)
        calendar.timeZone = TimeZone(identifier: "UTC") ?? TimeZone(secondsFromGMT: 0)!
        calendar.locale = Locale(identifier: "en_US_POSIX")
        return calendar
    }

    private static let monthYearFormatter: DateFormatter = {
        let formatter = DateFormatter()
        formatter.calendar = calendar
        formatter.locale = Locale(identifier: "en_US")
        formatter.timeZone = TimeZone(secondsFromGMT: 0)
        formatter.dateFormat = "LLLL yyyy"
        return formatter
    }()

    private static func parse(_ raw: String, pattern: String) -> Date {
        if raw.isEmpty {
            return Date()
        }
        if let date = formatter(pattern).date(from: raw) {
            return date
        }
        if let date = formatter("yyyy-MM-dd").date(from: raw) {
            return date
        }
        if let date = formatter("dd-MM-yyyy").date(from: raw) {
            return date
        }
        return Date()
    }

    private static func format(_ date: Date, pattern: String) -> String {
        formatter(pattern).string(from: date)
    }

    private static func formatter(_ pattern: String) -> DateFormatter {
        let formatter = DateFormatter()
        formatter.locale = Locale(identifier: "en_US_POSIX")
        formatter.calendar = calendar
        formatter.timeZone = TimeZone(secondsFromGMT: 0)
        formatter.dateFormat = pattern
        return formatter
    }

    private static func hexColor(_ hex: String) -> Color? {
        let cleaned = hex.trimmingCharacters(in: CharacterSet.alphanumerics.inverted)
        var rgb: UInt64 = 0
        guard Scanner(string: cleaned).scanHexInt64(&rgb), cleaned.count == 6 else { return nil }

        return Color(
            red: Double((rgb & 0xFF0000) >> 16) / 255,
            green: Double((rgb & 0x00FF00) >> 8) / 255,
            blue: Double(rgb & 0x0000FF) / 255
        )
    }
}
