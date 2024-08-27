local MyGearExporterFrame = CreateFrame("Frame", "GearExporterFrame", UIParent, "BasicFrameTemplate")
MyGearExporterFrame:SetSize(450, 350)
MyGearExporterFrame:SetPoint("CENTER")
MyGearExporterFrame:Hide()

local background = MyGearExporterFrame:CreateTexture(nil, "BACKGROUND")
background:SetAllPoints(MyGearExporterFrame)
background:SetColorTexture(0.1, 0.1, 0.1, 0.8)

MyGearExporterFrame.title = MyGearExporterFrame:CreateFontString(nil, "OVERLAY", "GameFontHighlight")
MyGearExporterFrame.title:SetPoint("CENTER", MyGearExporterFrame.TitleBg, "CENTER", 0, 0)
MyGearExporterFrame.title:SetText("Gear Exporter")

local MyGearExporterEditBox = CreateFrame("EditBox", nil, MyGearExporterFrame)
MyGearExporterEditBox:SetSize(420, 250)
MyGearExporterEditBox:SetPoint("TOP", MyGearExporterFrame, "TOP", 0, -30)
MyGearExporterEditBox:SetMultiLine(true)
MyGearExporterEditBox:SetFontObject("GameFontHighlightSmall")
MyGearExporterEditBox:SetAutoFocus(false)
MyGearExporterEditBox:SetScript("OnEscapePressed", function(self) self:ClearFocus() end)

local closeButton = CreateFrame("Button", nil, MyGearExporterFrame, "UIPanelButtonTemplate")
closeButton:SetSize(100, 30)
closeButton:SetPoint("BOTTOM", MyGearExporterFrame, "BOTTOM", -60, 10)
closeButton:SetText("Close")
closeButton:SetScript("OnClick", function() MyGearExporterFrame:Hide() end)

StaticPopupDialogs["COPY_URL"] = {
    text = " ",
    button1 = "OK",
    timeout = 0,
    whileDead = true,
    hideOnEscape = true,
    OnShow = function(self)
        local urlEditBox = CreateFrame("EditBox", nil, self, "InputBoxTemplate")
        urlEditBox:SetSize(250, 30)
        urlEditBox:SetPoint("TOP", self, "TOP", 0, -5)
        urlEditBox:SetText("Copy URL: https://builder.raith.one")
        urlEditBox:SetCursorPosition(0)
        urlEditBox:SetAutoFocus(true)
        urlEditBox:SetScript("OnEscapePressed", function() urlEditBox:ClearFocus() end)
        urlEditBox:SetScript("OnEnterPressed", function() urlEditBox:ClearFocus() end)
        self:SetHeight(150)
    end,
}

local linkButton = CreateFrame("Button", nil, MyGearExporterFrame, "UIPanelButtonTemplate")
linkButton:SetSize(100, 30)
linkButton:SetPoint("BOTTOM", MyGearExporterFrame, "BOTTOM", 60, 10)
linkButton:SetText("Website")
linkButton:SetScript("OnClick", function() 
    StaticPopup_Show("COPY_URL")
end)

local function GetGearInfo()
    local gearData = {}
    table.insert(gearData, "Slot,ItemID,ItemName,ItemSubType,EnchantID,GemIDs")

    for slot = 1, 17 do
        if slot ~= 4 then
            local itemLink = GetInventoryItemLink("player", slot)
            if itemLink then
                local itemId = GetInventoryItemID("player", slot)
                local enchantId = select(3, GetItemInfoInstant(itemLink))
                local itemName, _, _, _, _, itemSubType = GetItemInfo(itemLink)

                local gemIds = {}
                local gem1, gem2, gem3 = GetInventoryItemGems(slot)
                if gem1 then table.insert(gemIds, gem1) end
                if gem2 then table.insert(gemIds, gem2) end
                if gem3 then table.insert(gemIds, gem3) end

                local gemIdsString = table.concat(gemIds, ";") or "None"
                local csvLine = string.format("%d,%d,%s,%s,%s,%s", 
                                              slot, 
                                              itemId or "None", 
                                              itemName and string.gsub(itemName, ",", "") or "", 
                                              itemSubType or "", 
                                              enchantId or "None", 
                                              gemIdsString)
                table.insert(gearData, csvLine)
            else
                print("No item link found for slot:", slot)
            end
        end
    end

    return gearData
end

local function ShowGearExporterPopup(csvData)
    MyGearExporterFrame:Show()
    MyGearExporterEditBox:SetText(csvData)
    MyGearExporterEditBox:HighlightText()
    MyGearExporterEditBox:SetFocus()
end

SLASH_MYGEAREXPORT1 = '/exportgear'
function SlashCmdList.MYGEAREXPORT(msg, editBox)
    local gearInfo = GetGearInfo()
    local csvString = table.concat(gearInfo, "\n")
    ShowGearExporterPopup(csvString)
end

local function AddExportButton()
    local characterFrame = AscensionCharacterFrame
    local exportButton = CreateFrame("Button", "ExportGearButton", characterFrame, "UIPanelButtonTemplate")
    exportButton:SetSize(100, 30)
    exportButton:SetText("Export Gear")
    exportButton:SetPoint("TOP", characterFrame, "TOP", -175, -30)
    exportButton:SetScript("OnClick", function() 
        local gearInfo = GetGearInfo()
        local csvString = table.concat(gearInfo, "\n")
        ShowGearExporterPopup(csvString)
    end)
end

AscensionCharacterFrame:HookScript("OnShow", AddExportButton)
