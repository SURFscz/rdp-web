# rdp-web

Launch RDP session in a Browser

Credits:
https://www.researchgate.net/publication/355917270_Development_of_a_Concept_of_a_Modern_Desktop-as-a-Service

# High level design

The solution depends on following components:

- Windows Server with features enabled:

  - Active Directory (AD)
  - Internet Information Services (IIS)
  - Remote Desktop Services

- Guacamole/Authentication proxy service

The idea is that the Windows Server is may or mayt not be accessible via public internet. If it is, then users ar not able to authenticate on this service because they have no credential.
One reason to make this Windows Server accessible via Internet could be to allow an operator to logon for maintenance.
The Windows Service must be accessible by the Guacamole/Authentication proxy Service.

Users can make use of an RDP session via their local Web Browser and open a session via the Guacamole/Authentication proxy Service. They will have to authenticate first via OIDC with SRAM. If that succeeds, the proxy service contact the upstream Windows Service via an API call. This API request will create/update the user indentity in the Windows Server Active Directory and set a long random password on this identity. The password is returned to the proxy Service and there a guacamole websockets connection with the Windows Service is instantiated using these credentials. The credentials are never shared or visible to the user.
