import { pgTable, serial, text, timestamp, integer, boolean, varchar } from 'drizzle-orm/pg-core';
import { relations } from 'drizzle-orm';

export const users = pgTable('users', {
  id: serial('id').primaryKey(),
  username: varchar('username', { length: 50 }).notNull().unique(),
  password: text('password').notNull(),
  createdAt: timestamp('created_at').defaultNow().notNull(),
  updatedAt: timestamp('updated_at').defaultNow().notNull(),
});

export const shipmentOrders = pgTable('shipment_orders', {
  id: serial('id').primaryKey(),
  orderType: varchar('order_type', { length: 20 }).notNull(), // 'astana' or 'regional'
  
  // Common fields for both order types
  pickupAddress: text('pickup_address').notNull(),
  readyTime: varchar('ready_time', { length: 10 }).notNull(),
  cargoType: text('cargo_type').notNull(),
  weight: varchar('weight', { length: 20 }).notNull(),
  dimensions: text('dimensions').notNull(),
  contactName: varchar('contact_name', { length: 100 }).notNull(),
  contactPhone: varchar('contact_phone', { length: 20 }).notNull(),
  notes: text('notes'),
  
  // Regional-specific fields
  pickupCity: varchar('pickup_city', { length: 100 }), // for regional orders
  destinationCity: varchar('destination_city', { length: 100 }), // for regional orders
  deliveryAddress: text('delivery_address'), // for regional orders
  deliveryMethod: varchar('delivery_method', { length: 50 }), // for regional orders
  desiredArrivalDate: varchar('desired_arrival_date', { length: 20 }), // for regional orders
  
  // Meta fields
  status: varchar('status', { length: 20 }).notNull().default('new'), // 'new', 'processing', 'completed', 'cancelled'
  createdAt: timestamp('created_at').defaultNow().notNull(),
  updatedAt: timestamp('updated_at').defaultNow().notNull(),
});

// Define relations
export const usersRelations = relations(users, ({ many }) => ({
  // Users can manage multiple orders (admin relationship)
}));

export const shipmentOrdersRelations = relations(shipmentOrders, ({ one }) => ({
  // Orders are managed by admin users but don't have direct foreign key
}));

// Types for TypeScript
export type User = typeof users.$inferSelect;
export type InsertUser = typeof users.$inferInsert;
export type ShipmentOrder = typeof shipmentOrders.$inferSelect;
export type InsertShipmentOrder = typeof shipmentOrders.$inferInsert;