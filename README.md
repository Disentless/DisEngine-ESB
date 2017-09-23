# DisEngine-ESB
Server engine based on event system.

## Overview
Customizable engine that supports endless expansion. Server side uses events to share changes in data with clients. Clients receive information via EventStream protocol.

Consists of 2 parts:
- API. Handles requests and raises events. Client-to-server connection.
- EventStream. Waits for events and sends updates to a client. Server-to-client connection.

## Server mechanics
### Event creation
For each database table a separate class is required. Each class' fields should mimic the table structure (data type, restrictions, etc.). There are only 3 event types for a class:
- added (record added to the table)
- changed (record is changed)
- deleted (record was deleted)

After a class' method 'update' or 'delete' succeeds the event system is notified.

### Event raising
The event system is notified after data changes. 'raise' method accepts class name and an event type. Actual raised event name will be: <className>-<eventType>, where <className> is the name of representing class for some DB table (class name could be different from actual Db table it represents) and <eventType> is one of 3: 'added', 'changed', 'deleted'.

### Event processing
Each client will have one or more EventStream connections with the server. For each connection a certain number of subsriptions will be provided by the client. Subsriptions decide which of server events the client is notified of.
