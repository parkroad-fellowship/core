# Configure the Azure Provider
provider "azurerm" {
  features {}
  subscription_id = "2ecf7a2c-5bd5-4707-993e-e29509bdecb9"
  tenant_id       = 1
}

# Resource Group
resource "azurerm_resource_group" "rg" {
  name     = "prf-core-resource-group"
  location = "South Africa North" # Replace with your preferred Azure region
}

# Virtual Network
resource "azurerm_virtual_network" "vnet" {
  name                = "prf-core-vnet"
  address_space       = ["10.0.0.0/16"]
  location            = azurerm_resource_group.rg.location
  resource_group_name = azurerm_resource_group.rg.name
}

# Subnet
resource "azurerm_subnet" "subnet" {
  name                 = "prf-core-subnet"
  resource_group_name  = azurerm_resource_group.rg.name
  virtual_network_name = azurerm_virtual_network.vnet.name
  address_prefixes     = ["10.0.1.0/24"]

  # Enable Service Endpoint for Microsoft.Storage
  service_endpoints = ["Microsoft.Storage"]
}

# Network Interface
resource "azurerm_network_interface" "nic" {
  name                = "prf-core-nic"
  location            = azurerm_resource_group.rg.location
  resource_group_name = azurerm_resource_group.rg.name

  ip_configuration {
    name                          = "prf-core-ip-configuration"
    subnet_id                     = azurerm_subnet.subnet.id
    private_ip_address_allocation = "Dynamic"
    public_ip_address_id          = azurerm_public_ip.public_ip.id
  }
}

# Public IP
resource "azurerm_public_ip" "public_ip" {
  name                = "prf-core-public-ip"
  location            = azurerm_resource_group.rg.location
  resource_group_name = azurerm_resource_group.rg.name
  allocation_method   = "Static"
}

# Virtual Machine
resource "azurerm_linux_virtual_machine" "vm" {
  name                  = "prf-core-vm"
  location              = azurerm_resource_group.rg.location
  resource_group_name   = azurerm_resource_group.rg.name
  network_interface_ids = [azurerm_network_interface.nic.id]
  size                  = "Standard_B2as_v2"

  admin_username = "azureuser"
  admin_ssh_key {
    username   = "azureuser"
    public_key = file("/Users/adulu/Work/PRF/SuperApp/devops/keys/id_prfops.pub") # Replace with the path to your SSH public key
  }

  os_disk {
    caching              = "ReadWrite"
    storage_account_type = "Premium_LRS"
    disk_size_gb         = 64
  }

  source_image_reference {
    publisher = "Canonical"
    offer     = "UbuntuServer"
    sku       = "18_04-lts-gen2"
    version   = "18.04.202401161"
  }
}

# Security Group (NSG)
resource "azurerm_network_security_group" "nsg" {
  name                = "prf-core-nsg"
  location            = azurerm_resource_group.rg.location
  resource_group_name = azurerm_resource_group.rg.name

  # Allow SSH (port 22)
  security_rule {
    name                       = "SSH"
    priority                   = 1001
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_port_range          = "*"
    destination_port_range     = "22"
    source_address_prefix      = "*"
    destination_address_prefix = "*"
  }

  # Allow HTTP (port 80)
  security_rule {
    name                       = "HTTP"
    priority                   = 1002
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_port_range          = "*"
    destination_port_range     = "80"
    source_address_prefix      = "*"
    destination_address_prefix = "*"
  }

  # Allow HTTPS (port 443)
  security_rule {
    name                       = "HTTPS"
    priority                   = 1003
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_port_range          = "*"
    destination_port_range     = "443"
    source_address_prefix      = "*"
    destination_address_prefix = "*"
  }

  # Allow PostgreSQL (port 5432)
  security_rule {
    name                       = "PostgreSQL"
    priority                   = 1004
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_port_range          = "*"
    destination_port_range     = "5432"
    source_address_prefix      = "*"
    destination_address_prefix = "*"
  }

  # Allow Dragonfly (port 6379)
  security_rule {
    name                       = "Dragonfly"
    priority                   = 1005
    direction                  = "Inbound"
    access                     = "Allow"
    protocol                   = "Tcp"
    source_port_range          = "*"
    destination_port_range     = "6379"
    source_address_prefix      = "*"
    destination_address_prefix = "*"
  }
}


# Associate NSG with NIC
resource "azurerm_network_interface_security_group_association" "nsg_association" {
  network_interface_id      = azurerm_network_interface.nic.id
  network_security_group_id = azurerm_network_security_group.nsg.id
}

# CDN Profile
resource "azurerm_cdn_profile" "cdn_profile" {
  name                = "prf-core-cdn-profile"
  location            = "global"
  resource_group_name = azurerm_resource_group.rg.name
  sku                 = "Standard_Microsoft"
}

# CDN Endpoint
resource "azurerm_cdn_endpoint" "cdn_endpoint" {
  name                = "prf-core-cdn-endpoint"
  profile_name        = azurerm_cdn_profile.cdn_profile.name
  location            = "global"
  resource_group_name = azurerm_resource_group.rg.name


  origin {
    name      = "storage-account-origin"
    host_name = azurerm_storage_account.storage_account.primary_blob_host
  }

  origin_host_header = azurerm_storage_account.storage_account.primary_blob_host

  # is_compression_enabled = true

  delivery_rule {
    name  = "EnforceHTTPS"
    order = 1

    request_scheme_condition {
      operator     = "Equal"
      match_values = ["HTTP"]
    }

    url_redirect_action {
      redirect_type = "Found"
      protocol      = "Https"
    }
  }
}

# Storage Account
resource "azurerm_storage_account" "storage_account" {
  name                          = "prfcorestorage" # Must be globally unique
  resource_group_name           = azurerm_resource_group.rg.name
  location                      = azurerm_resource_group.rg.location
  account_tier                  = "Standard"
  account_replication_type      = "LRS"
  public_network_access_enabled = true # Enable public access

  network_rules {

    # default_action = "Deny" # Deny by default for enhanced security
    default_action = "Allow" # Allow by default for public access
    ip_rules = [
      "YOUR_SERVER_IP", # Core VM External IP
    ]
    virtual_network_subnet_ids = [
      azurerm_subnet.subnet.id # Allow access from specific subnet
    ]
    bypass = ["AzureServices"] # Allow trusted Azure services
  }

  blob_properties {
    cors_rule {
      allowed_headers = ["*"]
      allowed_methods = ["GET", "POST", "PUT"]
      allowed_origins = [
        "https://prf.test",
        "https://app.parkroadfellowship.org",
      ]
      exposed_headers    = ["*"]
      max_age_in_seconds = 3600
    }
  }

  depends_on = [azurerm_subnet.subnet] # Ensure subnet is updated first

  tags = {
    environment = "production"
  }
}

# Assign Storage Blob Data Contributor Role to Managed Identity
resource "azurerm_role_assignment" "vm_blob_data_contributor" {
  principal_id         = azurerm_user_assigned_identity.vm_identity.principal_id
  role_definition_name = "Storage Blob Data Contributor"
  scope                = azurerm_storage_account.storage_account.id
}

# Blob Container
resource "azurerm_storage_container" "blob_container" {
  name                  = "prf-core-container"
  storage_account_id    = azurerm_storage_account.storage_account.id
  container_access_type = "private" # Restrict blob access to authenticated users

}

# Managed Identity for VM
resource "azurerm_user_assigned_identity" "vm_identity" {
  name                = "prf-core-vm-identity"
  location            = azurerm_resource_group.rg.location
  resource_group_name = azurerm_resource_group.rg.name
}

# Speech to Text Services
resource "azurerm_cognitive_account" "speech_service" {
  name                = "prf-core-speech-service"
  location            = azurerm_resource_group.rg.location
  resource_group_name = azurerm_resource_group.rg.name
  kind                = "SpeechServices"
  sku_name            = "S0" # Standard tier, adjust based on your needs

  tags = {
    environment = "production"
  }
}

# Output the speech service key and endpoint
output "speech_service_key" {
  value     = azurerm_cognitive_account.speech_service.primary_access_key
  sensitive = true
}

output "speech_service_endpoint" {
  value = azurerm_cognitive_account.speech_service.endpoint
}

# Storage Account Key Output (Optional)
output "storage_account_primary_key" {
  value     = azurerm_storage_account.storage_account.primary_access_key
  sensitive = true
}

