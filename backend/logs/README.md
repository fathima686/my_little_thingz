# Shiprocket Automation Logs

This directory contains logs for Shiprocket automation events.

## Log Files

### `shiprocket_automation.log`
Contains all automation events including:
- Shipment creation success/failure
- Courier assignment details
- Pickup scheduling status
- Error messages

## Log Format

```
YYYY-MM-DD HH:MM:SS [level] message
```

Example:
```
2025-01-15 14:30:45 [info] Shipment created for order #123
2025-01-15 14:30:47 [info] Courier assigned for order #123: Delhivery Air
2025-01-15 14:30:50 [error] Auto-shipment creation failed for order #124: Invalid address
```

## Viewing Logs

### Windows Command Line:
```cmd
type shiprocket_automation.log
```

### PowerShell:
```powershell
Get-Content shiprocket_automation.log -Tail 50
```

### Text Editor:
```
notepad shiprocket_automation.log
```

## Log Levels

- `[info]` - Normal operation events
- `[error]` - Errors that need attention
- `[warning]` - Warnings (not critical)

## Monitoring

Check logs regularly to ensure automation is working correctly. Look for:
- ✅ Successful shipment creations
- ✅ Courier assignments
- ❌ Any error patterns
- ⚠️ Failed automations

## Troubleshooting

If you see errors:
1. Check the error message
2. Verify Shiprocket token is valid
3. Ensure pickup location exists
4. Verify order has valid shipping address
5. Check Shiprocket dashboard for more details

## Log Rotation

Logs are appended continuously. To prevent large files:
- Archive old logs periodically
- Clear logs after reviewing
- Implement log rotation if needed