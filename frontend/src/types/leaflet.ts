declare module 'leaflet' {
  export class LatLng {
    constructor(lat: number, lng: number);
    lat: number;
    lng: number;
    alt?: number;
    distanceTo(other: LatLng): number;
    equals(other: LatLng): boolean;
    toString(): string;
    wrap(): LatLng;
    toBounds(sizeInMeters: number): LatLngBounds;
  }

  export class LatLngBounds {
    constructor(southWest: LatLng, northEast: LatLng);
    constructor(latlngs: LatLng[]);
    extend(latlng: LatLng | LatLngBounds): this;
    getCenter(): LatLng;
    getSouthWest(): LatLng;
    getNorthEast(): LatLng;
    getNorthWest(): LatLng;
    getSouthEast(): LatLng;
    getWest(): number;
    getSouth(): number;
    getEast(): number;
    getNorth(): number;
    contains(other: LatLng | LatLngBounds): boolean;
    intersects(other: LatLngBounds): boolean;
    overlaps(other: LatLngBounds): boolean;
    toBBoxString(): string;
    equals(other: LatLngBounds): boolean;
    isValid(): boolean;
  }

  export interface MapOptions {
    center?: LatLng;
    zoom?: number;
    layers?: unknown[];
    minZoom?: number;
    maxZoom?: number;
    [key: string]: unknown;
  }

  export class Map {
    constructor(element: string | HTMLElement, options?: MapOptions);
    getCenter(): LatLng;
    getZoom(): number;
    getBounds(): LatLngBounds;
    fitBounds(bounds: LatLngBounds, options?: unknown): this;
    setView(center: LatLng, zoom?: number, options?: unknown): this;
    [key: string]: unknown;
  }

  export class Icon {
    constructor(options: unknown);
    static Default: unknown;
  }

  export class Marker {
    constructor(latlng: LatLng, options?: unknown);
    getLatLng(): LatLng;
    setLatLng(latlng: LatLng): this;
    [key: string]: unknown;
  }

  export function map(element: string | HTMLElement, options?: MapOptions): Map;
  export function marker(latlng: LatLng, options?: unknown): Marker;
  export function latLng(lat: number, lng: number): LatLng;
  export function latLngBounds(southWest: LatLng, northEast: LatLng): LatLngBounds;
}

declare module 'react-leaflet' {
  import * as React from 'react';
  import { Map, LatLng } from 'leaflet';

  export interface MapContainerProps {
    center?: [number, number] | LatLng;
    zoom?: number;
    style?: React.CSSProperties;
    children?: React.ReactNode;
    [key: string]: unknown;
  }
  export const MapContainer: React.FC<MapContainerProps>;

  export interface TileLayerProps {
    url: string;
    attribution?: string;
    [key: string]: unknown;
  }
  export const TileLayer: React.FC<TileLayerProps>;

  export interface MarkerProps {
    position: [number, number] | LatLng;
    children?: React.ReactNode;
    draggable?: boolean;
    eventHandlers?: unknown;
    [key: string]: unknown;
  }
  export const Marker: React.FC<MarkerProps>;

  export interface PopupProps {
    children?: React.ReactNode;
    [key: string]: unknown;
  }
  export const Popup: React.FC<PopupProps>;

  export interface PolylineProps {
    positions: ([number, number] | LatLng)[];
    pathOptions?: unknown;
    [key: string]: unknown;
  }
  export const Polyline: React.FC<PolylineProps>;

  export interface CircleProps {
    center: [number, number] | LatLng;
    radius: number;
    pathOptions?: unknown;
    [key: string]: unknown;
  }
  export const Circle: React.FC<CircleProps>;

  export function useMap(): Map;
}
