import { users, shipmentOrders, type User, type InsertUser, type ShipmentOrder, type InsertShipmentOrder } from "../shared/schema";
import { db } from "./db";
import { eq, desc, and, like } from "drizzle-orm";

// Interface definition for storage operations
export interface IStorage {
  // User operations
  getUser(id: number): Promise<User | undefined>;
  getUserByUsername(username: string): Promise<User | undefined>;
  createUser(insertUser: InsertUser): Promise<User>;
  
  // Shipment order operations
  createShipmentOrder(order: InsertShipmentOrder): Promise<ShipmentOrder>;
  getShipmentOrders(filters?: {
    orderType?: string;
    status?: string;
    search?: string;
    limit?: number;
    offset?: number;
  }): Promise<ShipmentOrder[]>;
  getShipmentOrder(id: number): Promise<ShipmentOrder | undefined>;
  updateShipmentOrderStatus(id: number, status: string): Promise<ShipmentOrder | undefined>;
}

// Database storage implementation
export class DatabaseStorage implements IStorage {
  async getUser(id: number): Promise<User | undefined> {
    const [user] = await db.select().from(users).where(eq(users.id, id));
    return user || undefined;
  }

  async getUserByUsername(username: string): Promise<User | undefined> {
    const [user] = await db.select().from(users).where(eq(users.username, username));
    return user || undefined;
  }

  async createUser(insertUser: InsertUser): Promise<User> {
    const [user] = await db
      .insert(users)
      .values(insertUser)
      .returning();
    return user;
  }

  async createShipmentOrder(order: InsertShipmentOrder): Promise<ShipmentOrder> {
    const [newOrder] = await db
      .insert(shipmentOrders)
      .values(order)
      .returning();
    return newOrder;
  }

  async getShipmentOrders(filters: {
    orderType?: string;
    status?: string;
    search?: string;
    limit?: number;
    offset?: number;
  } = {}): Promise<ShipmentOrder[]> {
    let query = db.select().from(shipmentOrders);
    
    const conditions = [];
    
    if (filters.orderType) {
      conditions.push(eq(shipmentOrders.orderType, filters.orderType));
    }
    
    if (filters.status) {
      conditions.push(eq(shipmentOrders.status, filters.status));
    }
    
    if (filters.search) {
      conditions.push(
        like(shipmentOrders.contactName, `%${filters.search}%`)
      );
    }
    
    if (conditions.length > 0) {
      query = query.where(and(...conditions));
    }
    
    query = query.orderBy(desc(shipmentOrders.createdAt));
    
    if (filters.limit) {
      query = query.limit(filters.limit);
    }
    
    if (filters.offset) {
      query = query.offset(filters.offset);
    }
    
    return await query;
  }

  async getShipmentOrder(id: number): Promise<ShipmentOrder | undefined> {
    const [order] = await db.select().from(shipmentOrders).where(eq(shipmentOrders.id, id));
    return order || undefined;
  }

  async updateShipmentOrderStatus(id: number, status: string): Promise<ShipmentOrder | undefined> {
    const [updatedOrder] = await db
      .update(shipmentOrders)
      .set({ 
        status,
        updatedAt: new Date()
      })
      .where(eq(shipmentOrders.id, id))
      .returning();
    return updatedOrder || undefined;
  }
}

export const storage = new DatabaseStorage();