# DisEngine-ESB
Server engine based on event system.

## Overview
Customizable engine that supports endless expansion. Server side uses events to share changes in data with clients. Clients receive information via EventStream protocol.

Consists of 2 parts:
- API. Handles requests and raises events. Client-to-server connection.
- EventStream. Waits for events and sends updates to a client. Server-to-client connection.

## Server mechanics
### Event creating
For each type of data server can process a separate class needs to be created and registered. Registering a class means that engine connects that class with an event group. Each class is given an event group, each data group is given an event subgroup, and each operation on that data is given a certain event.

For example, Class Account can handle data groups: login, passwords, updateTimestamp, etc. The class itself can be a data group. Registering class properly would result in creating following events:
- account-this-added
- account-this-changed
- account-this-deleted
- account-login-changed
- account-updateTimestamp-changed

Number of created events depends on settings for each class (number of data groups, allowed operations, etc.).

### Event raising
Each event created for a class will be raised after the handling method for that event succeeds. Methods that affect data but have no attached events will not raise an event.

### Event processing
Each client will have one or more EventStream connections with the server. For each connection a certain number of subsriptions will be provided by the client. Subsriptions decide which of server events the client is notified of.
