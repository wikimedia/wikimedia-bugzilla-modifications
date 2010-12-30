
  WeeklyReporting, version 0.1

  This Bugzilla extension provides a functionality to send weekly
  reports to user(s) regarding the week's bug activity


  WHAT YOU GET

  After you set up everything as described below, you'll send weekly
  status reports to the e-mail addresses specified.


  OVERVIEW

  This extension was created to replace the ad-hoc script used by
  Wikimedia to generate weekly Bugzilla reports. Right now, the
  original report is the only one. However, there could be more,
  in theory.


  INSTALLATION

  Configuration of WeeklyReporting

  - just store the module in the extension folder of your 3.6
    Bugzilla installation. It will be activated automatically
    and requires no further configuraiton.

  Configuration of Bugzilla

  - Use the Parameters interface or edit data/params directly to
    set up the following values

      # weeklyreporting_email - E-mail address (or more than one,
      by comma-delimiting them

  NOTES:
  - Cannot currently configure reports to have different recipients


Keywords: reporting, statistics


