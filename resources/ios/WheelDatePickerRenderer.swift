import SwiftUI

/// Renders the `wheel_date_picker` EDGE element on iOS.
///
/// SwiftUI ships a genuine wheel picker style (`.pickerStyle(.wheel)`), so unlike
/// the Android renderer this doesn't hand-roll scrolling - three synced `Picker`s
/// in an HStack give the same iOS-native feel as the original webview design.
struct WheelDatePickerRenderer: View {
    let node: NativeUINode

    @State private var selectedDate: Date = Date()
    @State private var initialized: Bool = false

    private static let isoFormatter: DateFormatter = {
        let f = DateFormatter()
        f.dateFormat = "yyyy-MM-dd"
        f.calendar = Calendar(identifier: .gregorian)
        f.timeZone = TimeZone(identifier: "UTC")
        return f
    }()

    private static let monthYearFormatter: DateFormatter = {
        let f = DateFormatter()
        f.dateFormat = "LLLL yyyy"
        return f
    }()

    var body: some View {
        let p = node.props

        let title = p.getString("title", default: "Select date")
        let yearStart = p.getInt("year_start", default: 1990)
        let yearEnd = p.getInt("year_end", default: Calendar.current.component(.year, from: Date()) + 20)
        let showFooter = p.getBool("show_footer", default: true)

        let colorCard = p.getColor("color_card", default: WheelDatePickerRenderer.hexColor("#151B30"))
        let colorBorder = p.getColor("color_border", default: WheelDatePickerRenderer.hexColor("#232A45"))
        let colorText = p.getColor("color_text", default: WheelDatePickerRenderer.hexColor("#E7EAF5"))
        let colorMuted = p.getColor("color_muted", default: WheelDatePickerRenderer.hexColor("#5B6280"))
        let colorAccent = p.getColor("color_accent", default: WheelDatePickerRenderer.hexColor("#7C9BFF"))

        let onChangeCb = p.getCallbackId("on_change")
        let onDoneCb = p.getCallbackId("on_done")
        let onCancelCb = p.getCallbackId("on_cancel")

        let calendar = Calendar(identifier: .gregorian)
        let years = yearStart <= yearEnd ? Array(yearStart...yearEnd) : [yearStart]
        let months = calendar.monthSymbols

        VStack(alignment: .leading, spacing: 12) {
            Text(title)
                .font(.system(size: 16, weight: .semibold))
                .foregroundColor(colorText)

            HStack {
                Button {
                    if let d = calendar.date(byAdding: .month, value: -1, to: selectedDate) {
                        selectedDate = d
                    }
                } label: {
                    Text("‹").font(.system(size: 20)).foregroundColor(colorAccent)
                }

                Spacer()

                Text(Self.monthYearFormatter.string(from: selectedDate))
                    .font(.system(size: 15, weight: .medium))
                    .foregroundColor(colorText)

                Spacer()

                Button {
                    if let d = calendar.date(byAdding: .month, value: 1, to: selectedDate) {
                        selectedDate = d
                    }
                } label: {
                    Text("›").font(.system(size: 20)).foregroundColor(colorAccent)
                }
            }

            HStack(spacing: 0) {
                Picker("Day", selection: dayBinding(calendar: calendar)) {
                    ForEach(1...daysInMonth(calendar: calendar), id: \.self) { d in
                        Text("\(d)").tag(d)
                    }
                }
                .pickerStyle(.wheel)
                .labelsHidden()

                Picker("Month", selection: monthBinding(calendar: calendar)) {
                    ForEach(Array(months.enumerated()), id: \.offset) { idx, name in
                        Text(name).tag(idx + 1)
                    }
                }
                .pickerStyle(.wheel)
                .labelsHidden()

                Picker("Year", selection: yearBinding(calendar: calendar)) {
                    ForEach(years, id: \.self) { y in
                        Text(String(y)).tag(y)
                    }
                }
                .pickerStyle(.wheel)
                .labelsHidden()
            }
            .frame(height: 180)
            .accentColor(colorAccent)
            .compositingGroup()

            if showFooter {
                HStack {
                    Button("Today") {
                        selectedDate = Date()
                        NativeElementBridge.sendTextChangeEvent(
                            onChangeCb, nodeId: node.id, value: Self.isoFormatter.string(from: selectedDate)
                        )
                    }
                    .font(.system(size: 14))
                    .foregroundColor(colorAccent)

                    Spacer()

                    Button("Cancel") {
                        NativeElementBridge.sendTextChangeEvent(
                            onCancelCb, nodeId: node.id, value: Self.isoFormatter.string(from: selectedDate)
                        )
                    }
                    .font(.system(size: 14))
                    .foregroundColor(colorMuted)
                    .padding(.trailing, 16)

                    Button("Done") {
                        NativeElementBridge.sendTextChangeEvent(
                            onDoneCb, nodeId: node.id, value: Self.isoFormatter.string(from: selectedDate)
                        )
                    }
                    .font(.system(size: 14, weight: .semibold))
                    .foregroundColor(colorAccent)
                }
            }
        }
        .padding(16)
        .background(colorCard)
        .overlay(
            RoundedRectangle(cornerRadius: 20).stroke(colorBorder, lineWidth: 1)
        )
        .clipShape(RoundedRectangle(cornerRadius: 20))
        .onAppear {
            if !initialized {
                let value = p.getString("value", default: Self.isoFormatter.string(from: Date()))
                selectedDate = Self.isoFormatter.date(from: value) ?? Date()
                initialized = true
            }
        }
        .onChange(of: selectedDate) { newValue in
            NativeElementBridge.sendTextChangeEvent(
                onChangeCb, nodeId: node.id, value: Self.isoFormatter.string(from: newValue)
            )
        }
    }

    // MARK: - Bindings

    private func daysInMonth(calendar: Calendar) -> Int {
        calendar.range(of: .day, in: .month, for: selectedDate)?.count ?? 31
    }

    private func dayBinding(calendar: Calendar) -> Binding<Int> {
        Binding(
            get: { calendar.component(.day, from: selectedDate) },
            set: { newDay in
                var comps = calendar.dateComponents([.year, .month, .day], from: selectedDate)
                comps.day = newDay
                if let d = calendar.date(from: comps) { selectedDate = d }
            }
        )
    }

    private func monthBinding(calendar: Calendar) -> Binding<Int> {
        Binding(
            get: { calendar.component(.month, from: selectedDate) },
            set: { newMonth in
                var comps = calendar.dateComponents([.year, .month, .day], from: selectedDate)
                let maxDayDate = calendar.date(from: DateComponents(year: comps.year, month: newMonth)) ?? selectedDate
                let maxDay = calendar.range(of: .day, in: .month, for: maxDayDate)?.count ?? 28
                comps.month = newMonth
                comps.day = min(comps.day ?? 1, maxDay)
                if let d = calendar.date(from: comps) { selectedDate = d }
            }
        )
    }

    private func yearBinding(calendar: Calendar) -> Binding<Int> {
        Binding(
            get: { calendar.component(.year, from: selectedDate) },
            set: { newYear in
                var comps = calendar.dateComponents([.year, .month, .day], from: selectedDate)
                let maxDayDate = calendar.date(from: DateComponents(year: newYear, month: comps.month)) ?? selectedDate
                let maxDay = calendar.range(of: .day, in: .month, for: maxDayDate)?.count ?? 28
                comps.year = newYear
                comps.day = min(comps.day ?? 1, maxDay)
                if let d = calendar.date(from: comps) { selectedDate = d }
            }
        )
    }

    // MARK: - Color helper

    private static func hexColor(_ hex: String) -> Color {
        let cleaned = hex.trimmingCharacters(in: CharacterSet.alphanumerics.inverted)
        var rgb: UInt64 = 0
        Scanner(string: cleaned).scanHexInt64(&rgb)
        let r = Double((rgb & 0xFF0000) >> 16) / 255
        let g = Double((rgb & 0x00FF00) >> 8) / 255
        let b = Double(rgb & 0x0000FF) / 255
        return Color(red: r, green: g, blue: b)
    }
}
