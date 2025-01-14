$xml=@'
<?xml version="1.0" encoding="UTF-16"?>
<Task version="1.2" xmlns="http://schemas.microsoft.com/windows/2004/02/mit/task">
  <Triggers>
    <EventTrigger>
      <Enabled>true</Enabled>
      <Subscription>&lt;QueryList&gt;&lt;Query Id="0" Path="Microsoft-Windows-TerminalServices-LocalSessionManager/Operational"&gt;&lt;Select Path="Microsoft-Windows-TerminalServices-LocalSessionManager/Operational"&gt;*[System[Provider[@Name='Microsoft-Windows-TerminalServices-LocalSessionManager'] and (EventID=21 or EventID=24 or EventID=25)]]&lt;/Select&gt;&lt;/Query&gt;&lt;/QueryList&gt;</Subscription>
      <ValueQueries>
        <Value name="e">Event/System/EventID</Value>
        <Value name="c">Event/System/Computer</Value>
        <Value name="i">Event/UserData/EventXML/Address</Value>
        <Value name="u">Event/UserData/EventXML/User</Value>
      </ValueQueries>
    </EventTrigger>
  </Triggers>
  <Principals>
    <Principal id="Author">
      <UserId>S-1-5-20</UserId>
      <RunLevel>LeastPrivilege</RunLevel>
    </Principal>
  </Principals>
  <Settings>
    <MultipleInstancesPolicy>Parallel</MultipleInstancesPolicy>
    <DisallowStartIfOnBatteries>false</DisallowStartIfOnBatteries>
    <StopIfGoingOnBatteries>false</StopIfGoingOnBatteries>
    <AllowHardTerminate>true</AllowHardTerminate>
    <StartWhenAvailable>false</StartWhenAvailable>
    <RunOnlyIfNetworkAvailable>false</RunOnlyIfNetworkAvailable>
    <IdleSettings>
      <StopOnIdleEnd>false</StopOnIdleEnd>
      <RestartOnIdle>false</RestartOnIdle>
    </IdleSettings>
    <AllowStartOnDemand>true</AllowStartOnDemand>
    <Enabled>true</Enabled>
    <Hidden>false</Hidden>
    <RunOnlyIfIdle>false</RunOnlyIfIdle>
    <WakeToRun>false</WakeToRun>
    <ExecutionTimeLimit>PT1H</ExecutionTimeLimit>
    <Priority>7</Priority>
  </Settings>
  <Actions Context="Author">
    <Exec>
      <Command>powershell.exe</Command>
	  <Arguments>"@{date=$((Get-Date).ToString('yyyy-MM-dd HH:mm:ss.fff'));action=$(if("$(e)" -in(21,25)){'rdplogin'}elseif("$(e)" -eq 24){'rdplogout'}else{'unknown'});event_id="$(e)";user='"$(u)"';ip='"$(i)"';computer='"$(c)"'}|ConvertTo-Json|iwr -Headers @{Authorization='$REPLACE_AUTH_CODE'} -Method POST -Uri $REPLACE_DOMAIN/api/log"</Arguments>
    </Exec>
  </Actions>
</Task>
'@

# Delete the task if it exists
if(Get-ScheduledTask -TaskName "txtlog" -ErrorAction Ignore){
	Unregister-ScheduledTask -TaskName "txtlog" -Confirm:$false
}

Register-ScheduledTask -Xml $xml -TaskName 'txtlog'
# Unfortunately there does not seem to be a foolproof way to create a task in a way that always works, so try again as the user calling this script
if(!$?) {
	Register-ScheduledTask -Xml $xml -TaskName 'txtlog' -User (whoami)
}

