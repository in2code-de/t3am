# any -> v4.0

Changes:
* The table `tx_t3amserver_client` was renamed to `tx_t3am_client`.\
  Since TYPO3 can not handle table renames you have to execute a migration wizard.

TODO:
* Activate Instance as a T3AM Server in Extension Settings.
* Run the `T3AM Client record migration`.
* Delete the old client table afterwards.
