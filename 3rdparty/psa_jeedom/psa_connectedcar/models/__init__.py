# coding: utf-8

# flake8: noqa
"""
    Groupe PSA Connected Car - WEB API B2C

    *PSA B2C Connected Car API*  # Introduction This is the description of the *Groupe PSA Connected Car V2 API*. The speccification is  is based on **OpenAPI Specification version 3** and can be displayed via [ReDoc](https://github.com/Rebilly/ReDoc)a or [Swagger](http://swagger.io).   This API allows applications to fetch data from the connected Vehicles data platform. # Authentication PSA Connected Car APIs uses the [OAuth 2.0](https://tools.ietf.org/html/rfc6749) protocol for authentication and Authorization. any application require a valid [Access Token](https://tools.ietf.org/html/rfc6749#section-1.4) to access to user data. # Errors   Error codes returned by all REST APIs comply with the standard. Nevertheless, PSA Services (callers) need to have more complete data structures (even when the answer is not Http-OK) to better detail the type of error by providing application code, message and a debugging code(for investigation purposes). The http code of the response is managed by the protocol itself (in the header).      **Errors are  returned as a generic error response:**    * ```xError``` object model.       # noqa: E501

    OpenAPI spec version: 4.0
    
    Generated by: https://github.com/swagger-api/swagger-codegen.git
"""


from __future__ import absolute_import

# import models into model package
from psa_connectedcar.models.adas import Adas
from psa_connectedcar.models.adas_park_assist import AdasParkAssist
from psa_connectedcar.models.alert import Alert
from psa_connectedcar.models.alert_end_position import AlertEndPosition
from psa_connectedcar.models.alert_links import AlertLinks
from psa_connectedcar.models.alert_msg_enum import AlertMsgEnum
from psa_connectedcar.models.alerts import Alerts
from psa_connectedcar.models.alerts_embedded import AlertsEmbedded
from psa_connectedcar.models.battery import Battery
from psa_connectedcar.models.bounded_program import BoundedProgram
from psa_connectedcar.models.charging_status_enum import ChargingStatusEnum
from psa_connectedcar.models.circle_zone import CircleZone
from psa_connectedcar.models.circle_zone_coordinates import CircleZoneCoordinates
from psa_connectedcar.models.collection_result import CollectionResult
from psa_connectedcar.models.collision import Collision
from psa_connectedcar.models.collision_links import CollisionLinks
from psa_connectedcar.models.collision_obj import CollisionObj
from psa_connectedcar.models.collision_obj_front import CollisionObjFront
from psa_connectedcar.models.collisions import Collisions
from psa_connectedcar.models.collisions_embedded import CollisionsEmbedded
from psa_connectedcar.models.created_at_field import CreatedAtField
from psa_connectedcar.models.data_monitor_trigger import DataMonitorTrigger
from psa_connectedcar.models.data_trigger import DataTrigger
from psa_connectedcar.models.default_alert_push import DefaultAlertPush
from psa_connectedcar.models.default_alert_push_attributes import DefaultAlertPushAttributes
from psa_connectedcar.models.doors_state import DoorsState
from psa_connectedcar.models.doors_state_opening import DoorsStateOpening
from psa_connectedcar.models.e_coaching import ECoaching
from psa_connectedcar.models.e_coaching_links import ECoachingLinks
from psa_connectedcar.models.e_coaching_scores import ECoachingScores
from psa_connectedcar.models.energy import Energy
from psa_connectedcar.models.energy_battery import EnergyBattery
from psa_connectedcar.models.energy_battery_health import EnergyBatteryHealth
from psa_connectedcar.models.energy_charging import EnergyCharging
from psa_connectedcar.models.engine import Engine
from psa_connectedcar.models.engine_oil import EngineOil
from psa_connectedcar.models.environment import Environment
from psa_connectedcar.models.environment_luminosity import EnvironmentLuminosity
from psa_connectedcar.models.event import Event
from psa_connectedcar.models.event_links import EventLinks
from psa_connectedcar.models.extension import Extension
from psa_connectedcar.models.extension_type import ExtensionType
from psa_connectedcar.models.geometry import Geometry
from psa_connectedcar.models.ignition import Ignition
from psa_connectedcar.models.index_range import IndexRange
from psa_connectedcar.models.kinetic import Kinetic
from psa_connectedcar.models.lighting import Lighting
from psa_connectedcar.models.link import Link
from psa_connectedcar.models.maintenance import Maintenance
from psa_connectedcar.models.maintenance_links import MaintenanceLinks
from psa_connectedcar.models.maintenance_obj import MaintenanceObj
from psa_connectedcar.models.monitor import Monitor
from psa_connectedcar.models.monitor_id import MonitorId
from psa_connectedcar.models.monitor_links import MonitorLinks
from psa_connectedcar.models.monitor_parameter import MonitorParameter
from psa_connectedcar.models.monitor_parameter_trigger_param import MonitorParameterTriggerParam
from psa_connectedcar.models.monitor_ref import MonitorRef
from psa_connectedcar.models.monitor_ref_links import MonitorRefLinks
from psa_connectedcar.models.monitor_status import MonitorStatus
from psa_connectedcar.models.monitor_status_setter import MonitorStatusSetter
from psa_connectedcar.models.monitor_subscribe import MonitorSubscribe
from psa_connectedcar.models.monitor_subscribe_batch_notify import MonitorSubscribeBatchNotify
from psa_connectedcar.models.monitor_subscribe_retry_policy import MonitorSubscribeRetryPolicy
from psa_connectedcar.models.monitor_trigger import MonitorTrigger
from psa_connectedcar.models.monitor_webhook import MonitorWebhook
from psa_connectedcar.models.monitor_webhook_attributes import MonitorWebhookAttributes
from psa_connectedcar.models.monitors import Monitors
from psa_connectedcar.models.monitors_embedded import MonitorsEmbedded
from psa_connectedcar.models.overall_autonomy import OverallAutonomy
from psa_connectedcar.models.point import Point
from psa_connectedcar.models.polygon_zone import PolygonZone
from psa_connectedcar.models.position import Position
from psa_connectedcar.models.position_properties import PositionProperties
from psa_connectedcar.models.preconditioning import Preconditioning
from psa_connectedcar.models.preconditioning_air_conditioning import PreconditioningAirConditioning
from psa_connectedcar.models.preconditioning_program import PreconditioningProgram
from psa_connectedcar.models.privacy import Privacy
from psa_connectedcar.models.program import Program
from psa_connectedcar.models.program_occurence import ProgramOccurence
from psa_connectedcar.models.safety import Safety
from psa_connectedcar.models.service_type import ServiceType
from psa_connectedcar.models.status import Status
from psa_connectedcar.models.status_embedded import StatusEmbedded
from psa_connectedcar.models.status_extension_type import StatusExtensionType
from psa_connectedcar.models.status_links import StatusLinks
from psa_connectedcar.models.tab_links import TabLinks
from psa_connectedcar.models.telemetry import Telemetry
from psa_connectedcar.models.telemetry_embedded import TelemetryEmbedded
from psa_connectedcar.models.telemetry_enum import TelemetryEnum
from psa_connectedcar.models.telemetry_extension import TelemetryExtension
from psa_connectedcar.models.telemetry_extension_type import TelemetryExtensionType
from psa_connectedcar.models.telemetry_message import TelemetryMessage
from psa_connectedcar.models.telemetry_message_embedded import TelemetryMessageEmbedded
from psa_connectedcar.models.telemetry_message_vehicle import TelemetryMessageVehicle
from psa_connectedcar.models.telemetry_message_vehicle_braking_system import TelemetryMessageVehicleBrakingSystem
from psa_connectedcar.models.telemetry_message_vehicle_transmission import TelemetryMessageVehicleTransmission
from psa_connectedcar.models.telemetry_message_vehicle_transmission_gearbox import TelemetryMessageVehicleTransmissionGearbox
from psa_connectedcar.models.telemetry_message_vehicle_transmission_gearbox_gear import TelemetryMessageVehicleTransmissionGearboxGear
from psa_connectedcar.models.telemetry_message_vehicle_transmission_gearbox_mode import TelemetryMessageVehicleTransmissionGearboxMode
from psa_connectedcar.models.time_monitor_trigger import TimeMonitorTrigger
from psa_connectedcar.models.time_range import TimeRange
from psa_connectedcar.models.time_stamped import TimeStamped
from psa_connectedcar.models.time_trigger import TimeTrigger
from psa_connectedcar.models.time_zone_monitor_trigger import TimeZoneMonitorTrigger
from psa_connectedcar.models.time_zone_trigger import TimeZoneTrigger
from psa_connectedcar.models.trip import Trip
from psa_connectedcar.models.trip_avg_consumption import TripAvgConsumption
from psa_connectedcar.models.trip_links import TripLinks
from psa_connectedcar.models.trips import Trips
from psa_connectedcar.models.trips_embedded import TripsEmbedded
from psa_connectedcar.models.updated_field import UpdatedField
from psa_connectedcar.models.url import Url
from psa_connectedcar.models.user import User
from psa_connectedcar.models.user_embedded import UserEmbedded
from psa_connectedcar.models.user_links import UserLinks
from psa_connectedcar.models.vect2_d import Vect2D
from psa_connectedcar.models.vehicle import Vehicle
from psa_connectedcar.models.vehicle_engine import VehicleEngine
from psa_connectedcar.models.vehicle_links import VehicleLinks
from psa_connectedcar.models.vehicle_odometer import VehicleOdometer
from psa_connectedcar.models.vehicles import Vehicles
from psa_connectedcar.models.vehicles_embedded import VehiclesEmbedded
from psa_connectedcar.models.way_points import WayPoints
from psa_connectedcar.models.way_points_embedded import WayPointsEmbedded
from psa_connectedcar.models.x_error import XError
from psa_connectedcar.models.zone_monitor_trigger import ZoneMonitorTrigger
from psa_connectedcar.models.zone_trigger import ZoneTrigger
from psa_connectedcar.models.zone_trigger_place import ZoneTriggerPlace
from psa_connectedcar.models.zone_trigger_place_center import ZoneTriggerPlaceCenter
