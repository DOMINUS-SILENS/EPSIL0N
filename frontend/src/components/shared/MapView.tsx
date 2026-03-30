import { LatLng, LatLngBounds } from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { useEffect, useMemo, type ComponentProps } from 'react';
import { Circle, MapContainer, Marker, Polyline, Popup, TileLayer, useMap } from 'react-leaflet';

export interface MapMarker {
  id: string;
  position: [number, number];
  title: string;
  description?: string;
  status?: 'pending' | 'in_progress' | 'completed' | 'failed' | 'skipped';
  type?: 'stop' | 'vehicle' | 'customer' | 'depot';
  draggable?: boolean;
  onDragEnd?: (position: [number, number]) => void;
}

export interface MapRoute {
  id: string;
  positions: [number, number][];
  color?: string;
  weight?: number;
}

export interface MapGeofence {
  id: string;
  center: [number, number];
  radius: number;
  color?: string;
}

interface MapViewProps {
  center?: [number, number];
  zoom?: number;
  markers?: MapMarker[];
  routes?: MapRoute[];
  geofences?: MapGeofence[];
  fitBounds?: boolean;
  height?: string;
  onMapClick?: (position: [number, number]) => void;
  onMarkerClick?: (markerId: string) => void;
  className?: string;
}

function MapBoundsController({ markers, fitBounds }: { markers: MapMarker[]; fitBounds?: boolean }) {
  const map = useMap();

  useEffect(() => {
    if (fitBounds && markers.length > 0) {
      const bounds = new LatLngBounds(markers.map((m) => new LatLng(m.position[0], m.position[1])));
      map.fitBounds(bounds, { padding: [50, 50] });
    }
  }, [map, markers, fitBounds]);

  return null;
}

export function MapView({
  center = [14.7167, -17.4677],
  zoom = 13,
  markers = [],
  routes = [],
  geofences = [],
  fitBounds = false,
  height = '400px',
  onMapClick,
  onMarkerClick,
  className = '',
}: MapViewProps) {
  const eventHandlers = useMemo(
    () => ({
      click: (e: { latlng: LatLng }) => {
        onMapClick?.([e.latlng.lat, e.latlng.lng]);
      },
    }),
    [onMapClick]
  );

  const getMarkerColor = (status?: string, type?: string): string => {
    if (status === 'completed') return '#22c55e';
    if (status === 'in_progress') return '#3b82f6';
    if (status === 'failed') return '#ef4444';
    if (status === 'skipped') return '#9ca3af';
    if (type === 'vehicle') return '#f59e0b';
    if (type === 'depot') return '#8b5cf6';
    return '#6b7280';
  };

  return (
    <div className={`rounded-lg overflow-hidden border ${className}`} style={{ height }}>
      <MapContainer
        center={center}
        zoom={zoom}
        style={{ height: '100%', width: '100%' }}
        eventHandlers={eventHandlers}
      >
        <TileLayer
          attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
          url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
        />

        <MapBoundsController markers={markers} fitBounds={fitBounds} />

        {geofences.map((geofence) => (
          <Circle
            key={geofence.id}
            center={geofence.center}
            radius={geofence.radius}
            pathOptions={{
              color: geofence.color || '#3b82f6',
              fillOpacity: 0.2,
            } as ComponentProps<typeof Circle>['pathOptions']}
          />
        ))}

        {routes.map((route) => (
          <Polyline
            key={route.id}
            positions={route.positions}
            pathOptions={{
              color: route.color || '#3b82f6',
              weight: route.weight || 3,
            }}
          />
        ))}

        {markers.map((marker) => (
          <Marker
            key={marker.id}
            position={marker.position}
            draggable={marker.draggable}
            eventHandlers={{
              click: () => onMarkerClick?.(marker.id),
              dragend: (e: { target: unknown }) => {
                const latLng = (e.target as { getLatLng: () => LatLng }).getLatLng();
                marker.onDragEnd?.([latLng.lat, latLng.lng]);
              },
            }}
          >
            <Popup>
              <div className="space-y-1">
                <p className="font-semibold">{marker.title}</p>
                {marker.description && <p className="text-sm text-gray-600">{marker.description}</p>}
                {marker.status && (
                  <span
                    className="inline-block px-2 py-0.5 text-xs rounded-full"
                    style={{
                      backgroundColor: getMarkerColor(marker.status, marker.type),
                      color: 'white',
                    }}
                  >
                    {marker.status}
                  </span>
                )}
              </div>
            </Popup>
          </Marker>
        ))}
      </MapContainer>
    </div>
  );
}
